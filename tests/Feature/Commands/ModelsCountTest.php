<?php

use App\Testing\Models\Bill;
use App\Testing\Models\User;
use Carbon\Carbon;
use Eloquentize\LaravelClient\Commands\ModelsCount;
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

test('models-count command executes successfully', function () {
    // Create test data
    User::factory()->create(['name' => 'Test User', 'created_at' => now()]);

    // Execute command
    $this->artisan('eloquentize:models-count --modelsPath=Testing/Models')
        ->assertExitCode(Command::SUCCESS)
        ->expectsOutput('Models count data sent to eloquentize.');

    // Assert HTTP request was made
    Http::assertSent(function ($request) {
        return $request->url() == 'https://api.eloquentize.com/api/metrics/models' &&
               $request->hasHeader('Authorization', 'Bearer test-token');
    });
});

test('models-count command with specific date', function () {
    // Set a fixed date for testing
    Carbon::setTestNow(Carbon::parse('2023-01-15'));

    // Create test data
    User::factory()->create(['name' => 'Test User', 'created_at' => '2023-01-10']);

    // Execute command
    $this->artisan('eloquentize:models-count 10/01/2023 --modelsPath=Testing/Models')
        ->assertExitCode(Command::SUCCESS);

    // Assert HTTP request was made with correct data
    Http::assertSent(function ($request) {
        $data = $request->data();

        return $request->url() == 'https://api.eloquentize.com/api/metrics/models' &&
               $data['start']->format('Y-m-d') == '2023-01-10' &&
               $data['end']->format('Y-m-d') == '2023-01-10';
    });

    // Reset Carbon::now()
    Carbon::setTestNow();
});

test('models-count command with specific models', function () {
    // Create test data
    User::factory()->create(['name' => 'Test User', 'created_at' => now()]);
    Bill::factory()->create(['amount' => 100, 'created_at' => now()]);

    // Execute command
    $this->artisan('eloquentize:models-count --models=User --modelsPath=Testing/Models')
        ->assertExitCode(Command::SUCCESS);

    // Assert HTTP request was made with correct data
    Http::assertSent(function ($request) {
        $data = $request->data();
        $hasUserMetric = false;
        $hasBillMetric = false;

        foreach ($data['metrics'] as $metric) {
            if ($metric->label === 'User') {
                $hasUserMetric = true;
            }
            if ($metric->label === 'Bill') {
                $hasBillMetric = true;
            }
        }

        return $hasUserMetric && ! $hasBillMetric;
    });
});

test('models-count command with dry run option', function () {
    // Create test data
    User::factory()->create(['name' => 'Test User', 'created_at' => now()]);

    // Execute command
    $this->artisan('eloquentize:models-count --modelsPath=Testing/Models --dry')
        ->assertExitCode(Command::SUCCESS)
        ->expectsOutput('Dry run enabled. Data NOT sent to eloquentize.');

    // Assert no HTTP request was made
    Http::assertNothingSent();
});

test('models-count command with scope option requires models option', function () {
    // Execute command
    $this->artisan('eloquentize:models-count --scope=active --modelsPath=Testing/Models')
        ->assertExitCode(Command::FAILURE)
        ->expectsOutput('"scope" option requires "--models" option to be set. models provided should have a corresponding scope.');
});

test('models-count command with scopeValue option requires scope option', function () {
    // Execute command
    $this->artisan('eloquentize:models-count --scopeValue=true --modelsPath=Testing/Models')
        ->assertExitCode(Command::FAILURE)
        ->expectsOutput('"--scopeValue" option requires "--scope" option to be set.');
});

test('models-count command with no models found', function () {
    // Execute command with a non-existent models path
    $this->artisan('eloquentize:models-count --modelsPath=NonExistent')
        ->assertExitCode(Command::FAILURE)
        ->expectsOutput('No models found.');
});

test('models-count command with scope and scopeValue', function () {
    // Create test data
    User::factory()->create(['name' => 'Active User', 'active' => true, 'created_at' => now()]);
    User::factory()->create(['name' => 'Inactive User', 'active' => false, 'created_at' => now()]);

    // Add a scope to the User model
    User::macro('scopeActive', function ($query, $value = true) {
        return $query->where('active', $value);
    });

    // Execute command
    $this->artisan('eloquentize:models-count --models=User --scope=active --scopeValue=true --modelsPath=Testing/Models')
        ->assertExitCode(Command::SUCCESS);

    // Assert HTTP request was made with correct data
    Http::assertSent(function ($request) {
        $data = $request->data();
        $hasCorrectLabel = false;

        foreach ($data['metrics'] as $metric) {
            if ($metric->label === 'User::active(true)') {
                $hasCorrectLabel = true;
                break;
            }
        }

        return $hasCorrectLabel;
    });
});
