<?php

use Carbon\Carbon;
use Eloquentize\LaravelClient\Commands\Traits\DateArgument;

class DateArgumentTestClass
{
    use DateArgument;

    public $defaultDateFormat = 'Y-m-d';

    public function testResolveDate($dateArgument)
    {
        return $this->resolveDate($dateArgument);
    }
}

test('resolveDate returns today\'s date for "today"', function () {
    $handler = new DateArgumentTestClass;

    // Set a fixed date for testing
    Carbon::setTestNow(Carbon::parse('2023-01-15'));

    expect($handler->testResolveDate('today'))->toBe('2023-01-15');

    // Reset Carbon::now()
    Carbon::setTestNow();
});

test('resolveDate returns yesterday\'s date for "yesterday"', function () {
    $handler = new DateArgumentTestClass;

    // Set a fixed date for testing
    Carbon::setTestNow(Carbon::parse('2023-01-15'));

    expect($handler->testResolveDate('yesterday'))->toBe('2023-01-14');

    // Reset Carbon::now()
    Carbon::setTestNow();
});

test('resolveDate returns the input for other strings', function () {
    $handler = new DateArgumentTestClass;

    expect($handler->testResolveDate('2023-01-10'))->toBe('2023-01-10');
    expect($handler->testResolveDate('invalid-date'))->toBe('invalid-date');
});

test('resolveDate uses the defaultDateFormat', function () {
    $handler = new DateArgumentTestClass;
    $handler->defaultDateFormat = 'd/m/Y';

    // Set a fixed date for testing
    Carbon::setTestNow(Carbon::parse('2023-01-15'));

    expect($handler->testResolveDate('today'))->toBe('15/01/2023');
    expect($handler->testResolveDate('yesterday'))->toBe('14/01/2023');

    // Reset Carbon::now()
    Carbon::setTestNow();
});
