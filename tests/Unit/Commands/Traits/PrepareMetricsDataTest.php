<?php

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Eloquentize\LaravelClient\Commands\Traits\PrepareMetricsData;
use Illuminate\Support\Facades\Config;

class PrepareMetricsDataTestClass
{
    use PrepareMetricsData {
        prepareMetricsData as public;
    }
}

test('prepareMetricsData formats data correctly', function () {
    $handler = new PrepareMetricsDataTestClass;

    // Mock config values
    Config::shouldReceive('get')
        ->with('app.timezone')
        ->andReturn('Europe/Paris');
    Config::shouldReceive('get')
        ->with('app.url')
        ->andReturn('https://example.com');
    Config::shouldReceive('get')
        ->with('app.env')
        ->andReturn('testing');

    // Create test data
    $metrics = [
        'model1' => 10,
        'model2' => 20,
    ];
    $startDate = Carbon::parse('2023-01-15 00:00:00');
    $endDate = Carbon::parse('2023-01-15 23:59:59');
    $period = new CarbonPeriod($startDate, $endDate);
    $event = 'created_at';

    // Call the method
    $result = $handler->prepareMetricsData($metrics, $period, $event);

    // Assert the result
    expect($result)->toBeArray();
    expect($result['start'])->toBe($startDate);
    expect($result['end'])->toBe($endDate);
    expect($result['timezone'])->toBe('Europe/Paris');
    expect($result['app_url'])->toBe('https://example.com');
    expect($result['env'])->toBe('testing');
    expect($result['event_type'])->toBe('created_at');
    expect($result['metrics'])->toBe($metrics);
});

test('prepareMetricsData uses UTC as default timezone', function () {
    $handler = new PrepareMetricsDataTestClass;

    // Mock config values
    Config::shouldReceive('get')
        ->with('app.timezone')
        ->andReturnNull();
    Config::shouldReceive('get')
        ->with('app.url')
        ->andReturn('https://example.com');
    Config::shouldReceive('get')
        ->with('app.env')
        ->andReturn('testing');

    // Create test data
    $metrics = ['model1' => 10];
    $period = new CarbonPeriod(Carbon::now(), Carbon::now());
    $event = 'created_at';

    // Call the method
    $result = $handler->prepareMetricsData($metrics, $period, $event);

    // Assert the result
    expect($result['timezone'])->toBe('UTC');
});
