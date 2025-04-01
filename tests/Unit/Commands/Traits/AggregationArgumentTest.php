<?php

use Eloquentize\LaravelClient\Commands\Enums\AggregationType;
use Eloquentize\LaravelClient\Commands\Traits\AggregationArgument;

class AggregationArgumentTestClass
{
    use AggregationArgument;

    protected $errorMessage;

    public function error($message)
    {
        $this->errorMessage = $message;
    }

    public function getErrorMessage()
    {
        return $this->errorMessage;
    }

    public function testResolveAggregation($aggregationArgument)
    {
        return $this->resolveAggregation($aggregationArgument);
    }
}

test('resolveAggregation returns correct enum for valid aggregation type', function () {
    $handler = new AggregationArgumentTestClass;

    expect($handler->testResolveAggregation('sum'))->toBe(AggregationType::sum);
    expect($handler->testResolveAggregation('avg'))->toBe(AggregationType::avg);
    expect($handler->testResolveAggregation('max'))->toBe(AggregationType::max);
    expect($handler->testResolveAggregation('min'))->toBe(AggregationType::min);
});

test('resolveAggregation shows error for invalid aggregation type', function () {
    $handler = new AggregationArgumentTestClass;

    try {
        $handler->testResolveAggregation('invalid');
    } catch (\Exception $e) {
        // Exit is called in the trait, which will throw an exception in tests
    }

    expect($handler->getErrorMessage())->toContain('invalid is not a valid aggregation type');
    expect($handler->getErrorMessage())->toContain('min, max, avg, sum');
});
