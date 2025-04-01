<?php

use App\Testing\Models\Bill;
use Carbon\Carbon;
use Eloquentize\LaravelClient\Commands\PropertyAggregateLegacy;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;

beforeEach(function () {
    // Set up config
    Config::set('eloquentize.api_url', 'https://api.eloquentize.com');
    Config::set('eloquentize.ELOQUENTIZE_API_TOKEN', 'test-token');
    Config::set('app.url', 'https://example.com');
    Config::set('app.env', 'testing');
    Config::set('app.timezone', 'UTC');
});

test('property-aggregate-legacy command executes successfully', function () {
    // Create test data with different dates
    Bill::factory()->create(['amount' => 100, 'created_at' => now()->subDays(5)]);
    Bill::factory()->create(['amount' => 200, 'created_at' => now()->subDays(2)]);

    // Mock the getOldestDateFromModel and getArrayOfDays methods
    $this->mock(PropertyAggregateLegacy::class, function ($mock) {
        $mock->shouldReceive('getOldestDateFromModel')
            ->andReturn(Carbon::now()->subDays(2));
        $mock->shouldReceive('getArrayOfDays')
            ->andReturn([
                Carbon::now()->subDays(2),
                Carbon::now()->subDays(1),
            ]);
        $mock->shouldReceive('handle')->passthru();
    });

    // Mock the call method to avoid actually calling the property-aggregate command
    Artisan::shouldReceive('call')
        ->with('eloquentize:property-aggregate', Mockery::any())
        ->times(2) // Should be called twice, once for each day
        ->andReturn(0);

    // Execute command
    $this->artisan('eloquentize:property-aggregate-legacy Bill amount sum --modelsPath=Testing/Models')
        ->assertExitCode(Command::SUCCESS);
});

test('property-aggregate-legacy command with specific date', function () {
    // Create test data
    Bill::factory()->create(['amount' => 100, 'created_at' => now()->subDays(5)]);

    // Set a fixed date for testing
    Carbon::setTestNow(Carbon::parse('2023-01-15'));

    // Mock the getArrayOfDays method
    $this->mock(PropertyAggregateLegacy::class, function ($mock) {
        $mock->shouldReceive('getArrayOfDays')
            ->andReturn([
                Carbon::parse('2023-01-10'),
                Carbon::parse('2023-01-11'),
                Carbon::parse('2023-01-12'),
                Carbon::parse('2023-01-13'),
                Carbon::parse('2023-01-14'),
            ]);
        $mock->shouldReceive('handle')->passthru();
    });

    // Mock the call method
    Artisan::shouldReceive('call')
        ->with('eloquentize:property-aggregate', Mockery::on(function ($args) {
            return $args['model'] === 'Bill' &&
                   $args['property'] === 'amount' &&
                   $args['aggregation'] === 'sum';
        }))
        ->times(5) // Should be called 5 times, once for each day
        ->andReturn(0);

    // Execute command
    $this->artisan('eloquentize:property-aggregate-legacy Bill amount sum 2023-01-10 --modelsPath=Testing/Models')
        ->assertExitCode(Command::SUCCESS);

    // Reset Carbon::now()
    Carbon::setTestNow();
});

test('property-aggregate-legacy command with invalid date format', function () {
    // Execute command with invalid date
    $this->artisan('eloquentize:property-aggregate-legacy Bill amount sum invalid-date --modelsPath=Testing/Models')
        ->assertExitCode(Command::FAILURE)
        ->expectsOutput('Invalid date format. The date should be formatted according to the provided date format: d/m/Y');
});

test('property-aggregate-legacy command with custom date format', function () {
    // Create test data
    Bill::factory()->create(['amount' => 100, 'created_at' => now()->subDays(5)]);

    // Mock the getArrayOfDays method
    $this->mock(PropertyAggregateLegacy::class, function ($mock) {
        $mock->shouldReceive('getArrayOfDays')
            ->andReturn([
                Carbon::parse('2023-01-10'),
            ]);
        $mock->shouldReceive('handle')->passthru();
    });

    // Mock the call method
    Artisan::shouldReceive('call')
        ->with('eloquentize:property-aggregate', Mockery::on(function ($args) {
            return $args['model'] === 'Bill' &&
                   $args['property'] === 'amount' &&
                   $args['aggregation'] === 'sum' &&
                   $args['--dateFormat'] === 'd/m/Y';
        }))
        ->once()
        ->andReturn(0);

    // Execute command
    $this->artisan('eloquentize:property-aggregate-legacy Bill amount sum 10/01/2023 --dateFormat=d/m/Y --modelsPath=Testing/Models')
        ->assertExitCode(Command::SUCCESS);
});

test('property-aggregate-legacy command with custom event', function () {
    // Create test data
    Bill::factory()->create(['amount' => 100, 'created_at' => now()->subDays(5)]);

    // Mock the getArrayOfDays method
    $this->mock(PropertyAggregateLegacy::class, function ($mock) {
        $mock->shouldReceive('getOldestDateFromModel')
            ->andReturn(Carbon::now()->subDays(2));
        $mock->shouldReceive('getArrayOfDays')
            ->andReturn([
                Carbon::now()->subDays(2),
                Carbon::now()->subDays(1),
            ]);
        $mock->shouldReceive('handle')->passthru();
    });

    // Mock the call method
    Artisan::shouldReceive('call')
        ->with('eloquentize:property-aggregate', Mockery::on(function ($args) {
            return $args['model'] === 'Bill' &&
                   $args['property'] === 'amount' &&
                   $args['aggregation'] === 'sum' &&
                   $args['--event'] === 'updated_at';
        }))
        ->times(2)
        ->andReturn(0);

    // Execute command
    $this->artisan('eloquentize:property-aggregate-legacy Bill amount sum null updated_at --modelsPath=Testing/Models')
        ->assertExitCode(Command::SUCCESS);
});

test('property-aggregate-legacy command with scope and scopeValue', function () {
    // Create test data
    Bill::factory()->create(['amount' => 100, 'created_at' => now()->subDays(5)]);

    // Mock the getArrayOfDays method
    $this->mock(PropertyAggregateLegacy::class, function ($mock) {
        $mock->shouldReceive('getOldestDateFromModel')
            ->andReturn(Carbon::now()->subDays(2));
        $mock->shouldReceive('getArrayOfDays')
            ->andReturn([
                Carbon::now()->subDays(2),
                Carbon::now()->subDays(1),
            ]);
        $mock->shouldReceive('handle')->passthru();
    });

    // Mock the call method
    Artisan::shouldReceive('call')
        ->with('eloquentize:property-aggregate', Mockery::on(function ($args) {
            return $args['model'] === 'Bill' &&
                   $args['property'] === 'amount' &&
                   $args['aggregation'] === 'sum' &&
                   $args['--scope'] === 'highValue' &&
                   $args['--scopeValue'] === '100';
        }))
        ->times(2)
        ->andReturn(0);

    // Execute command
    $this->artisan('eloquentize:property-aggregate-legacy Bill amount sum --scope=highValue --scopeValue=100 --modelsPath=Testing/Models')
        ->assertExitCode(Command::SUCCESS);
});

test('property-aggregate-legacy command with invalid aggregation type', function () {
    // Execute command with invalid aggregation
    $this->artisan('eloquentize:property-aggregate-legacy Bill amount invalid --modelsPath=Testing/Models')
        ->assertExitCode(Command::FAILURE)
        ->expectsOutput('Eloquentize error occurred: invalid is not a valid aggregation type ( min, max, avg, sum )');
});
