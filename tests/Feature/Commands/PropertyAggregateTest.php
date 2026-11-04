<?php

use App\Testing\Models\Bill;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    // Set up config
    Config::set('eloquentize.api_url', 'https://api.eloquentize.com');
    Config::set('eloquentize.ELOQUENTIZE_API_TOKEN', 'test-token');
    Config::set('app.url', 'https://example.com');
    Config::set('app.env', 'testing');
    Config::set('app.timezone', 'UTC');

    // Fake HTTP responses
    Http::fake([
        'https://api.eloquentize.com/api/metrics/models' => Http::response(['status' => 'ok'], 200),
    ]);
});

test('property-aggregate command executes successfully with sum aggregation', function () {
    // Create test data
    Bill::factory()->create(['amount' => 100, 'created_at' => now()]);
    Bill::factory()->create(['amount' => 200, 'created_at' => now()]);

    // Execute command
    $this->artisan('eloquentize:property-aggregate Bill amount sum --modelsPath=Testing/Models')
        ->assertExitCode(Command::SUCCESS);

    // Assert HTTP request was made with correct data
    Http::assertSent(function ($request) {
        $data = $request->data();
        $hasCorrectLabel = false;
        $hasCorrectCount = false;

        foreach ($data['metrics'] as $metric) {
            if ($metric->label === 'Bill::amount->sum()') {
                $hasCorrectLabel = true;
                if ($metric->count === 300) { // 100 + 200
                    $hasCorrectCount = true;
                }
                break;
            }
        }

        return $hasCorrectLabel && $hasCorrectCount;
    });
});

test('property-aggregate command executes successfully with avg aggregation', function () {
    // Create test data
    Bill::factory()->create(['amount' => 100, 'created_at' => now()]);
    Bill::factory()->create(['amount' => 200, 'created_at' => now()]);

    // Execute command
    $this->artisan('eloquentize:property-aggregate Bill amount avg --modelsPath=Testing/Models')
        ->assertExitCode(Command::SUCCESS);

    // Assert HTTP request was made with correct data
    Http::assertSent(function ($request) {
        $data = $request->data();
        $hasCorrectLabel = false;
        $hasCorrectCount = false;

        foreach ($data['metrics'] as $metric) {
            if ($metric->label === 'Bill::amount->avg()') {
                $hasCorrectLabel = true;
                if ((float) $metric->count === 150.0) { // (100 + 200) / 2
                    $hasCorrectCount = true;
                }
                break;
            }
        }

        return $hasCorrectLabel && $hasCorrectCount;
    });
});

test('property-aggregate command executes successfully with max aggregation', function () {
    // Create test data
    Bill::factory()->create(['amount' => 100, 'created_at' => now()]);
    Bill::factory()->create(['amount' => 200, 'created_at' => now()]);

    // Execute command
    $this->artisan('eloquentize:property-aggregate Bill amount max --modelsPath=Testing/Models')
        ->assertExitCode(Command::SUCCESS);

    // Assert HTTP request was made with correct data
    Http::assertSent(function ($request) {
        $data = $request->data();
        $hasCorrectLabel = false;
        $hasCorrectCount = false;

        foreach ($data['metrics'] as $metric) {
            if ($metric->label === 'Bill::amount->max()') {
                $hasCorrectLabel = true;
                if ($metric->count === 200) {
                    $hasCorrectCount = true;
                }
                break;
            }
        }

        return $hasCorrectLabel && $hasCorrectCount;
    });
});

test('property-aggregate command executes successfully with min aggregation', function () {
    // Create test data
    Bill::factory()->create(['amount' => 100, 'created_at' => now()]);
    Bill::factory()->create(['amount' => 200, 'created_at' => now()]);

    // Execute command
    $this->artisan('eloquentize:property-aggregate Bill amount min --modelsPath=Testing/Models')
        ->assertExitCode(Command::SUCCESS);

    // Assert HTTP request was made with correct data
    Http::assertSent(function ($request) {
        $data = $request->data();
        $hasCorrectLabel = false;
        $hasCorrectCount = false;

        foreach ($data['metrics'] as $metric) {
            if ($metric->label === 'Bill::amount->min()') {
                $hasCorrectLabel = true;
                if ($metric->count === 100) {
                    $hasCorrectCount = true;
                }
                break;
            }
        }

        return $hasCorrectLabel && $hasCorrectCount;
    });
});

test('property-aggregate command with specific date', function () {
    // Set a fixed date for testing
    Carbon::setTestNow(Carbon::parse('2023-01-15'));

    // Create test data
    Bill::factory()->create(['amount' => 100, 'created_at' => '2023-01-10 10:00:00']);
    Bill::factory()->create(['amount' => 200, 'created_at' => '2023-01-10 15:00:00']);
    Bill::factory()->create(['amount' => 300, 'created_at' => '2023-01-11 10:00:00']);

    // Execute command (default date format is d/m/Y)
    $this->artisan('eloquentize:property-aggregate Bill amount sum 10/01/2023 --modelsPath=Testing/Models')
        ->assertExitCode(Command::SUCCESS);

    // Assert HTTP request was made with correct data
    Http::assertSent(function ($request) {
        $data = $request->data();

        $hasCorrectDate = Carbon::parse($data['start'])->format('Y-m-d') === '2023-01-10'
            && Carbon::parse($data['end'])->format('Y-m-d') === '2023-01-10';

        $hasCorrectCount = collect($data['metrics'])->contains(fn ($metric) => $metric->count === 300); // 100 + 200 (only from 2023-01-10)

        return $hasCorrectDate && $hasCorrectCount;
    });

    // Reset Carbon::now()
    Carbon::setTestNow();
});

test('property-aggregate command with a date that does not match the date format fails gracefully', function () {
    $this->artisan('eloquentize:property-aggregate Bill amount sum 2023-01-10 --modelsPath=Testing/Models')
        ->assertExitCode(Command::FAILURE);

    Http::assertNothingSent();
});

test('property-aggregate command with scope', function () {
    Bill::factory()->create(['amount' => 100, 'price' => 10, 'created_at' => now()]);
    Bill::factory()->create(['amount' => 200, 'price' => 1000, 'created_at' => now()]);

    // Execute command with the priceOver() scope and a value
    $this->artisan('eloquentize:property-aggregate Bill amount sum --scope=priceOver --scopeValue=500 --modelsPath=Testing/Models')
        ->assertExitCode(Command::SUCCESS);

    // Assert HTTP request was made with correct data
    Http::assertSent(function ($request) {
        $data = $request->data();

        return collect($data['metrics'])->contains(
            fn ($metric) => $metric->label === 'Bill::priceOver(500)::amount->sum()'
                && $metric->count === 200 // Only the bill with price > 500
        );
    });
});

test('property-aggregate command with invalid aggregation type', function () {
    // Execute command with invalid aggregation
    $this->artisan('eloquentize:property-aggregate Bill amount invalid --modelsPath=Testing/Models')
        ->assertExitCode(Command::FAILURE)
        ->expectsOutput('Eloquentize error occurred: invalid is not a valid aggregation type ( min, max, avg, sum )');
});

test('property-aggregate command with scopeValue option requires scope option', function () {
    // Execute command
    $this->artisan('eloquentize:property-aggregate Bill amount sum --scopeValue=true --modelsPath=Testing/Models')
        ->assertExitCode(Command::FAILURE)
        ->expectsOutput('"--scopeValue" option requires "--scope" option to be set.');
});

test('property-aggregate command with invalid model or property', function () {
    $this->artisan('eloquentize:property-aggregate NonExistentModel nonExistentProperty sum --modelsPath=Testing/Models')
        ->assertExitCode(Command::FAILURE);

    // Assert no HTTP request was made
    Http::assertNothingSent();
});
