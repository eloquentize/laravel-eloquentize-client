<?php

use Eloquentize\LaravelClient\Commands\Enums\AggregationType;

test('AggregationType has expected cases', function () {
    expect(AggregationType::cases())->toHaveCount(4);
    expect(AggregationType::sum->value)->toBe('sum');
    expect(AggregationType::avg->value)->toBe('avg');
    expect(AggregationType::max->value)->toBe('max');
    expect(AggregationType::min->value)->toBe('min');
});

test('AggregationType::from creates correct enum from string', function () {
    expect(AggregationType::from('sum'))->toBe(AggregationType::sum);
    expect(AggregationType::from('avg'))->toBe(AggregationType::avg);
    expect(AggregationType::from('max'))->toBe(AggregationType::max);
    expect(AggregationType::from('min'))->toBe(AggregationType::min);
});

test('AggregationType::from throws ValueError for invalid string', function () {
    expect(fn () => AggregationType::from('invalid'))->toThrow(\ValueError::class);
});
