<?php

use App\Testing\Models\Bill;
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

test('property-aggregate-overall command executes successfully with sum aggregation', function () {
    // Create test data with different dates
    Bill::factory()->create(['amount' => 100, 'created_at' => now()->subDays(5)]);
    Bill::factory()->create(['amount' => 200, 'created_at' => now()->subDays(2)]);
    Bill::factory()->create(['amount' => 300, 'created_at' => now()]);

    // Execute command
    $this->artisan('eloquentize:property-aggregate-overall Bill amount sum --modelsPath=Testing/Models')
        ->assertExitCode(Command::SUCCESS);

    // Assert HTTP request was made with correct data
    Http::assertSent(function ($request) {
        $data = $request->data();
        $hasCorrectLabel = false;
        $hasCorrectCount = false;

        foreach ($data['metrics'] as $metric) {
            if ($metric->label === 'Overall Bill::amount->sum()') {
                $hasCorrectLabel = true;
                if ($metric->count === 600) { // 100 + 200 + 300
                    $hasCorrectCount = true;
                }
                break;
            }
        }

        return $hasCorrectLabel && $hasCorrectCount;
    });
});

test('property-aggregate-overall command executes successfully with avg aggregation', function () {
    // Create test data with different dates
    Bill::factory()->create(['amount' => 100, 'created_at' => now()->subDays(5)]);
    Bill::factory()->create(['amount' => 200, 'created_at' => now()->subDays(2)]);
    Bill::factory()->create(['amount' => 300, 'created_at' => now()]);

    // Execute command
    $this->artisan('eloquentize:property-aggregate-overall Bill amount avg --modelsPath=Testing/Models')
        ->assertExitCode(Command::SUCCESS);

    // Assert HTTP request was made with correct data
    Http::assertSent(function ($request) {
        $data = $request->data();
        $hasCorrectLabel = false;
        $hasCorrectCount = false;

        foreach ($data['metrics'] as $metric) {
            if ($metric->label === 'Overall Bill::amount->avg()') {
                $hasCorrectLabel = true;
                if ((float) $metric->count === 200.0) { // (100 + 200 + 300) / 3
                    $hasCorrectCount = true;
                }
                break;
            }
        }

        return $hasCorrectLabel && $hasCorrectCount;
    });
});

test('property-aggregate-overall command executes successfully with max aggregation', function () {
    // Create test data with different dates
    Bill::factory()->create(['amount' => 100, 'created_at' => now()->subDays(5)]);
    Bill::factory()->create(['amount' => 200, 'created_at' => now()->subDays(2)]);
    Bill::factory()->create(['amount' => 300, 'created_at' => now()]);

    // Execute command
    $this->artisan('eloquentize:property-aggregate-overall Bill amount max --modelsPath=Testing/Models')
        ->assertExitCode(Command::SUCCESS);

    // Assert HTTP request was made with correct data
    Http::assertSent(function ($request) {
        $data = $request->data();
        $hasCorrectLabel = false;
        $hasCorrectCount = false;

        foreach ($data['metrics'] as $metric) {
            if ($metric->label === 'Overall Bill::amount->max()') {
                $hasCorrectLabel = true;
                if ($metric->count === 300) {
                    $hasCorrectCount = true;
                }
                break;
            }
        }

        return $hasCorrectLabel && $hasCorrectCount;
    });
});

test('property-aggregate-overall command executes successfully with min aggregation', function () {
    // Create test data with different dates
    Bill::factory()->create(['amount' => 100, 'created_at' => now()->subDays(5)]);
    Bill::factory()->create(['amount' => 200, 'created_at' => now()->subDays(2)]);
    Bill::factory()->create(['amount' => 300, 'created_at' => now()]);

    // Execute command
    $this->artisan('eloquentize:property-aggregate-overall Bill amount min --modelsPath=Testing/Models')
        ->assertExitCode(Command::SUCCESS);

    // Assert HTTP request was made with correct data
    Http::assertSent(function ($request) {
        $data = $request->data();
        $hasCorrectLabel = false;
        $hasCorrectCount = false;

        foreach ($data['metrics'] as $metric) {
            if ($metric->label === 'Overall Bill::amount->min()') {
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

test('property-aggregate-overall command with scope', function () {
    Bill::factory()->create(['amount' => 100, 'price' => 10, 'created_at' => now()->subDays(5)]);
    Bill::factory()->create(['amount' => 200, 'price' => 1000, 'created_at' => now()->subDays(2)]);
    Bill::factory()->create(['amount' => 300, 'price' => 2000, 'created_at' => now()]);

    // Execute command
    $this->artisan('eloquentize:property-aggregate-overall Bill amount sum --scope=expensive --modelsPath=Testing/Models')
        ->assertExitCode(Command::SUCCESS);

    // Assert HTTP request was made with correct data
    Http::assertSent(function ($request) {
        return collect($request->data()['metrics'])->contains(
            fn ($metric) => $metric->label === 'Overall Bill::expensive::amount->sum()'
                && $metric->count === 500 // 200 + 300 (only expensive bills)
        );
    });
});

test('property-aggregate-overall command with scope and scopeValue', function () {
    Bill::factory()->create(['amount' => 100, 'price' => 10, 'created_at' => now()->subDays(5)]);
    Bill::factory()->create(['amount' => 200, 'price' => 1000, 'created_at' => now()->subDays(2)]);
    Bill::factory()->create(['amount' => 300, 'price' => 2000, 'created_at' => now()]);

    // Execute command
    $this->artisan('eloquentize:property-aggregate-overall Bill amount sum --scope=priceOver --scopeValue=1500 --modelsPath=Testing/Models')
        ->assertExitCode(Command::SUCCESS);

    // Assert HTTP request was made with correct data
    Http::assertSent(function ($request) {
        return collect($request->data()['metrics'])->contains(
            fn ($metric) => $metric->label === 'Overall Bill::priceOver(1500)::amount->sum()'
                && $metric->count === 300 // Only the bill with price > 1500
        );
    });
});

test('property-aggregate-overall command with invalid aggregation type', function () {
    // Execute command with invalid aggregation
    $this->artisan('eloquentize:property-aggregate-overall Bill amount invalid --modelsPath=Testing/Models')
        ->assertExitCode(Command::FAILURE)
        ->expectsOutput('Eloquentize error occurred: invalid is not a valid aggregation type ( min, max, avg, sum )');
});

test('property-aggregate-overall command with scopeValue option requires scope option', function () {
    // Execute command
    $this->artisan('eloquentize:property-aggregate-overall Bill amount sum --scopeValue=true --modelsPath=Testing/Models')
        ->assertExitCode(Command::FAILURE)
        ->expectsOutput('"--scopeValue" option requires "--scope" option to be set.');
});

test('property-aggregate-overall command with invalid model or property', function () {
    $this->artisan('eloquentize:property-aggregate-overall NonExistentModel nonExistentProperty sum --modelsPath=Testing/Models')
        ->assertExitCode(Command::FAILURE);

    // Assert no HTTP request was made
    Http::assertNothingSent();
});

test('property-aggregate-overall command handles non-existent scope', function () {
    // Create test data
    Bill::factory()->create(['amount' => 100, 'created_at' => now()]);

    // Execute command
    $this->artisan('eloquentize:property-aggregate-overall Bill amount sum --scope=nonExistentScope --modelsPath=Testing/Models')
        ->assertExitCode(Command::SUCCESS)
        ->expectsOutput('Scope nonExistentScope does not exist on model Bill');

    // Assert HTTP request was still made
    Http::assertSent(function ($request) {
        return $request->url() == 'https://api.eloquentize.com/api/metrics/models';
    });
});
