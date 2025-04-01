<?php

use Eloquentize\LaravelClient\Commands\Traits\HasVerbose;
use Eloquentize\LaravelClient\Commands\Traits\SendMetricsData;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

class SendMetricsDataTestClass
{
    use HasVerbose;
    use SendMetricsData {
        sendMetricsData as public;
    }

    public $verbose = false;

    protected $messages = [];

    public function info($message)
    {
        $this->messages['info'][] = $message;
    }

    public function error($message)
    {
        $this->messages['error'][] = $message;
    }

    public function getMessages()
    {
        return $this->messages;
    }
}

test('sendMetricsData sends data to API and handles successful response', function () {
    $handler = new SendMetricsDataTestClass;

    // Mock config
    Config::shouldReceive('get')
        ->with('eloquentize.api_url')
        ->andReturn('https://api.eloquentize.com');

    // Mock HTTP response
    Http::fake([
        'https://api.eloquentize.com/api/metrics/models' => Http::response(['status' => 'ok'], 200),
    ]);

    // Test data
    $data = ['metrics' => ['model1' => 10]];
    $token = 'test-token';

    // Call the method
    $handler->sendMetricsData($data, $token);

    // Assert HTTP request was made correctly
    Http::assertSent(function ($request) use ($data, $token) {
        return $request->url() == 'https://api.eloquentize.com/api/metrics/models' &&
               $request->hasHeader('Authorization', 'Bearer '.$token) &&
               $request->hasHeader('Accept', 'application/json') &&
               $request->data() == $data;
    });

    // Assert success message was logged
    expect($handler->getMessages()['info'])->toContain('Data successfully sent to Eloquentize!');
});

test('sendMetricsData handles failed response', function () {
    $handler = new SendMetricsDataTestClass;

    // Mock config
    Config::shouldReceive('get')
        ->with('eloquentize.api_url')
        ->andReturn('https://api.eloquentize.com');

    // Mock HTTP response
    Http::fake([
        'https://api.eloquentize.com/api/metrics/models' => Http::response(['error' => 'Invalid data'], 400),
    ]);

    // Test data
    $data = ['metrics' => ['model1' => 10]];
    $token = 'test-token';

    // Call the method
    $handler->sendMetricsData($data, $token);

    // Assert error message was logged
    expect($handler->getMessages()['error'])->toContain('Data sending failed');
});

test('sendMetricsData handles exceptions', function () {
    $handler = new SendMetricsDataTestClass;
    $handler->verbose = true;

    // Mock config
    Config::shouldReceive('get')
        ->with('eloquentize.api_url')
        ->andReturn('https://api.eloquentize.com');

    // Mock HTTP to throw exception
    Http::shouldReceive('acceptJson')
        ->andThrow(new \Exception('Connection error'));

    // Test data
    $data = ['metrics' => ['model1' => 10]];
    $token = 'test-token';

    // Call the method
    $handler->sendMetricsData($data, $token);

    // Assert error message was logged
    expect($handler->getMessages()['error'])->toContain('Connection error');
});
