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

    // Set config values
    Config::set('app.timezone', 'Europe/Paris');
    Config::set('app.url', 'https://example.com');
    Config::set('app.env', 'testing');

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
    expect($result['start'])->toEqual($startDate);
    expect($result['end'])->toEqual($endDate);
    expect($result['timezone'])->toBe('Europe/Paris');
    expect($result['app_url'])->toBe('https://example.com');
    expect($result['env'])->toBe('testing');
    expect($result['event_type'])->toBe('created_at');
    expect($result['metrics'])->toBe($metrics);
});

test('prepareMetricsData uses UTC as default timezone', function () {
    $handler = new PrepareMetricsDataTestClass;

    // Set config values
    Config::set('app.timezone', null);
    Config::set('app.url', 'https://example.com');
    Config::set('app.env', 'testing');

    // Create test data
    $metrics = ['model1' => 10];
    $period = new CarbonPeriod(Carbon::now(), Carbon::now());
    $event = 'created_at';

    // Call the method
    $result = $handler->prepareMetricsData($metrics, $period, $event);

    // Assert the result
    expect($result['timezone'])->toBe('UTC');
});
