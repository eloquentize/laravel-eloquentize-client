<?php

namespace Eloquentize\LaravelClient\Commands;

use Carbon\CarbonPeriod;
use Eloquentize\LaravelClient\Commands\Enums\AggregationType;
use Eloquentize\LaravelClient\Commands\Traits\AggregationArgument;
use Eloquentize\LaravelClient\Commands\Traits\BuildPeriod;
use Eloquentize\LaravelClient\Commands\Traits\DateArgument;
use Eloquentize\LaravelClient\Commands\Traits\GatherModels;
use Eloquentize\LaravelClient\Commands\Traits\HasVerbose;
use Eloquentize\LaravelClient\Commands\Traits\PrepareMetricsData;
use Eloquentize\LaravelClient\Commands\Traits\SendMetricsData;

class PropertyAggregate extends BaseCommand
{
    use AggregationArgument, BuildPeriod, DateArgument, GatherModels, HasVerbose, PrepareMetricsData, SendMetricsData;

    protected $signature = 'eloquentize:property-aggregate {model} {property} {aggregation} {date?} {--event=created_at} {--periodType=daily} {--dateFormat=} {--modelsPath=}';

    protected $description = 'Perform a sum of a property of a model for a given date and event.';

    protected $verbose = false;

    public function perform(string $model, AggregationType $aggregation, string $property, CarbonPeriod $period, string $event, $modelsPath = null)
    {
        $metrics = [];
        $modelClass = $this->getModelClass($model, $modelsPath);

        if (! $this->isModelValid($modelClass, $property)) {
            //exit(1);
            //echo "\n".$modelClass;
            //echo "\n"."Model is not valid";
            return 1;
        }

        try {
            $method = $aggregation->value;
            // sound avg / min / max / sum return 0 if no records found ? for now
            $count = $modelClass::whereBetween($event, [$period->getStartDate(), $period->getEndDate()])->$method($property) ?? 0;
            //echo "handle\n ";
            //echo "The ".$method." of ".$model."->".$property." is : ".$count;
            $this->verbose('The '.$method.' of '.$model.'->'.$property.' is : '.$count);
            $label = $model.'::'.$property.'->'.$method.'()';
            $metrics[] = (object) ['label' => $label, 'count' => $count];
        } catch (\Exception $e) {
            $this->verbose('An error occurred: '.$e->getMessage(), 'error');

            return 1;
        }

        return $metrics;
    }

    public function handle()
    {

        $this->verbose = $this->option('verbose') ?? false;
        $model = $this->argument('model');
        $aggregation = $this->resolveAggregation($this->argument('aggregation'));
        $property = $this->argument('property');
        $event = $this->option('event') ?? 'created_at';
        $date = $this->resolveDate($this->argument('date') ?? 'today');
        $periodType = $this->option('periodType') ?? 'daily';
        $dateFormat = $this->option('dateFormat') ?? $this->defaultDateFormat;
        $modelsPath = $this->option('modelsPath');
        $period = $this->buildPeriod($date, $periodType, $dateFormat);
        $metrics = $this->perform($model, $aggregation, $property, $period, $event, $modelsPath);
        $metricsData = $this->prepareMetricsData($metrics, $period, $event);
        $this->sendMetricsData($metricsData, env('ELOQUENTIZE_API_TOKEN'));

        return 0;

    }
}
