<?php

namespace Eloquentize\LaravelClient\Commands\Traits;

use Eloquentize\LaravelClient\Commands\Enums\AggregationType;

trait AggregationArgument
{
    protected function resolveAggregation(string $aggregationArgument): AggregationType
    {
        try {
            $aggregation = AggregationType::from($aggregationArgument);

            return $aggregation;
        } catch (\ValueError $e) {
            $this->error('Eloquentize error occurred: '.$aggregationArgument.' is not a valid aggregation type ( min, max, avg, sum )');
            exit(1);
        }
    }
}
