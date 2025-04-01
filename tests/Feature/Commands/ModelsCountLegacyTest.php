 <?php

use App\Testing\Models\Bill;
use App\Testing\Models\User;
use Carbon\Carbon;
use Eloquentize\LaravelClient\Commands\ModelsCountLegacy;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
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

test('models-count-legacy command executes successfully', function () {
    // Create test data with different dates
    User::factory()->create(['name' => 'Test User 1', 'created_at' => now()->subDays(5)]);
    User::factory()->create(['name' => 'Test User 2', 'created_at' => now()->subDays(2)]);
    Bill::factory()->create(['amount' => 100, 'created_at' => now()->subDays(3)]);

    // Execute command
    $this->artisan('eloquentize:models-count-legacy --modelsPath=Testing/Models')
        ->assertExitCode(Command::SUCCESS);
});

test('models-count-legacy command with specific date', function () {
    // Create test data
    User::factory()->create(['name' => 'Test User', 'created_at' => now()->subDays(5)]);

    // Set a fixed date for testing
    Carbon::setTestNow(Carbon::parse('2023-01-15'));

    // Execute command
    $this->artisan('eloquentize:models-count-legacy 10/01/2023 --modelsPath=Testing/Models')
        ->assertExitCode(Command::SUCCESS);

    // Reset Carbon::now()
    Carbon::setTestNow();
});

test('models-count-legacy command with invalid date format', function () {
    // Execute command with invalid date
    $this->artisan('eloquentize:models-count-legacy invalid-date --modelsPath=Testing/Models')
        ->assertExitCode(Command::FAILURE)
        ->expectsOutput('Invalid date format. The date should be formatted according to the provided date format: d/m/Y');
});

test('models-count-legacy command with custom date format', function () {
    // Create test data
    User::factory()->create(['name' => 'Test User', 'created_at' => now()->subDays(5)]);

    // Execute command
    $this->artisan('eloquentize:models-count-legacy 10/01/2023 --dateFormat=d/m/Y --modelsPath=Testing/Models')
        ->assertExitCode(Command::SUCCESS);
});

test('models-count-legacy command with custom event', function () {
    // Create test data
    User::factory()->create(['name' => 'Test User', 'created_at' => now()->subDays(5)]);

    // Execute command
    $this->artisan('eloquentize:models-count-legacy --event=updated_at --modelsPath=Testing/Models')
        ->assertExitCode(Command::SUCCESS);
});

test('models-count-legacy command with specific models', function () {
    // Create test data
    User::factory()->create(['name' => 'Test User', 'created_at' => now()->subDays(5)]);
    Bill::factory()->create(['amount' => 100, 'created_at' => now()->subDays(3)]);

    // Execute command
    $this->artisan('eloquentize:models-count-legacy --models=User --modelsPath=Testing/Models')
        ->assertExitCode(Command::SUCCESS);
});

test('models-count-legacy command with scope and scopeValue', function () {
    // Create test data
    User::factory()->create(['name' => 'Test User', 'created_at' => now()->subDays(5)]);

    // Add a scope to the User model
    User::macro('scopeActive', function ($query, $value = true) {
        return $query->where('active', $value);
    });

    // Execute command
    $this->artisan('eloquentize:models-count-legacy --scope=active --scopeValue=true --modelsPath=Testing/Models')
        ->assertExitCode(Command::SUCCESS);
});

test('models-count-legacy command with no records found', function () {
    // Execute command with a non-existent models path
    $this->artisan('eloquentize:models-count-legacy --modelsPath=NonExistent')
        ->assertExitCode(Command::FAILURE)
        ->expectsOutput('No Records found.');
});
