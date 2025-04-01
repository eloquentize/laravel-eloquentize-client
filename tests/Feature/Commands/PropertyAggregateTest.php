<?php

use App\Testing\Models\Bill;
use Carbon\Carbon;
use Eloquentize\LaravelClient\Commands\PropertyAggregate;
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
                if ($metric->count === 150) { // (100 + 200) / 2
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
    Bill::factory()->create(['amount' => 100, 'created_at' => '2023-01-10']);
    Bill::factory()->create(['amount' => 200, 'created_at' => '2023-01-10']);
    Bill::factory()->create(['amount' => 300, 'created_at' => '2023-01-11']);

    // Execute command
    $this->artisan('eloquentize:property-aggregate Bill amount sum 2023-01-10 --modelsPath=Testing/Models')
        ->assertExitCode(Command::SUCCESS);

    // Assert HTTP request was made with correct data
    Http::assertSent(function ($request) {
        $data = $request->data();
        $hasCorrectDate = false;
        $hasCorrectCount = false;

        if ($data['start']->format('Y-m-d') === '2023-01-10' &&
            $data['end']->format('Y-m-d') === '2023-01-10') {
            $hasCorrectDate = true;
        }

        foreach ($data['metrics'] as $metric) {
            if ($metric->count === 300) { // 100 + 200 (only from 2023-01-10)
                $hasCorrectCount = true;
                break;
            }
        }

        return $hasCorrectDate && $hasCorrectCount;
    });

    // Reset Carbon::now()
    Carbon::setTestNow();
});

test('property-aggregate command with scope', function () {
    // Create a test model with a scope
    $billModel = new class extends Bill
    {
        public function scopeHighValue($query)
        {
            return $query->where('amount', '>=', 150);
        }
    };

    // Create test data
    $billModel::unguard();
    $billModel::create(['amount' => 100, 'created_at' => now()]);
    $billModel::create(['amount' => 200, 'created_at' => now()]);
    $billModel::reguard();

    // Mock methods
    $this->mock(PropertyAggregate::class, function ($mock) use ($billModel) {
        $mock->shouldReceive('getModelClass')
            ->andReturn(get_class($billModel));
        $mock->shouldReceive('isModelValid')
            ->andReturn(true);
        $mock->shouldReceive('handle')->passthru();
    });

    // Execute command
    $this->artisan('eloquentize:property-aggregate Bill amount sum --scope=highValue --modelsPath=Testing/Models')
        ->assertExitCode(Command::SUCCESS);

    // Assert HTTP request was made with correct data
    Http::assertSent(function ($request) {
        $data = $request->data();
        $hasCorrectLabel = false;
        $hasCorrectCount = false;

        foreach ($data['metrics'] as $metric) {
            if ($metric->label === 'Bill::highValue::amount->sum()') {
                $hasCorrectLabel = true;
                if ($metric->count === 200) { // Only the 200 value bill
                    $hasCorrectCount = true;
                }
                break;
            }
        }

        return $hasCorrectLabel && $hasCorrectCount;
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
    // Mock methods
    $this->mock(PropertyAggregate::class, function ($mock) {
        $mock->shouldReceive('isModelValid')
            ->andReturn(false);
        $mock->shouldReceive('handle')->passthru();
    });

    // Execute command
    $this->artisan('eloquentize:property-aggregate Bill nonExistentProperty sum --modelsPath=Testing/Models')
        ->assertExitCode(Command::SUCCESS);

    // Assert no HTTP request was made
    Http::assertNothingSent();
});
