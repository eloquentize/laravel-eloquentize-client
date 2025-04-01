<?php

use App\Testing\Models\User;
use Carbon\Carbon;
use Eloquentize\LaravelClient\Commands\ModelCountOverall;
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

test('model-count-overall command executes successfully', function () {
    // Create test data
    User::factory()->create(['name' => 'Test User 1', 'created_at' => now()->subDays(5)]);
    User::factory()->create(['name' => 'Test User 2', 'created_at' => now()->subDays(2)]);

    // Execute command
    $this->artisan('eloquentize:model-count-overall User --modelsPath=Testing/Models')
        ->assertExitCode(Command::SUCCESS);

    // Assert HTTP request was made
    Http::assertSent(function ($request) {
        return $request->url() == 'https://api.eloquentize.com/api/metrics/models' &&
               $request->hasHeader('Authorization', 'Bearer test-token');
    });
});

test('model-count-overall command with scope', function () {
    // Create test data
    User::factory()->create(['name' => 'Active User', 'active' => true, 'created_at' => now()->subDays(5)]);
    User::factory()->create(['name' => 'Inactive User', 'active' => false, 'created_at' => now()->subDays(2)]);

    // Add a scope to the User model
    User::macro('scopeActive', function ($query) {
        return $query->where('active', true);
    });

    // Execute command
    $this->artisan('eloquentize:model-count-overall User --scope=active --modelsPath=Testing/Models')
        ->assertExitCode(Command::SUCCESS);

    // Assert HTTP request was made
    Http::assertSent(function ($request) {
        return $request->url() == 'https://api.eloquentize.com/api/metrics/models';
    });
});

test('model-count-overall command with scope and scopeValue', function () {
    // Create test data
    User::factory()->create(['name' => 'Active User', 'status' => 'active', 'created_at' => now()->subDays(5)]);
    User::factory()->create(['name' => 'Pending User', 'status' => 'pending', 'created_at' => now()->subDays(2)]);

    // Add a scope to the User model
    User::macro('scopeStatus', function ($query, $status) {
        return $query->where('status', $status);
    });

    // Execute command
    $this->artisan('eloquentize:model-count-overall User --scope=status --scopeValue=active --modelsPath=Testing/Models')
        ->assertExitCode(Command::SUCCESS);

    // Assert HTTP request was made
    Http::assertSent(function ($request) {
        return $request->url() == 'https://api.eloquentize.com/api/metrics/models';
    });
});

test('model-count-overall command with scopeValue option requires scope option', function () {
    // Execute command
    $this->artisan('eloquentize:model-count-overall User --scopeValue=active --modelsPath=Testing/Models')
        ->assertExitCode(Command::FAILURE)
        ->expectsOutput('"--scopeValue" option requires "--scope" option to be set.');
});

test('model-count-overall command handles non-existent scope', function () {
    // Create test data
    User::factory()->create(['name' => 'Test User', 'created_at' => now()]);

    // Execute command
    $this->artisan('eloquentize:model-count-overall User --scope=nonExistentScope --modelsPath=Testing/Models')
        ->assertExitCode(Command::SUCCESS)
        ->expectsOutput('Scope nonExistentScope does not exist on model User');

    // Assert HTTP request was still made
    Http::assertSent(function ($request) {
        return $request->url() == 'https://api.eloquentize.com/api/metrics/models';
    });
});

test('model-count-overall command handles exceptions', function () {
    // Execute command with a non-existent models path
    $this->artisan('eloquentize:model-count-overall User --modelsPath=NonExistent')
        ->assertExitCode(Command::FAILURE);

    // Assert no HTTP request was made
    Http::assertNothingSent();
});
