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
use Illuminate\Support\Carbon;

class PropertyAggregateOverall extends BaseCommand
{
    use AggregationArgument, BuildPeriod, DateArgument, GatherModels, HasVerbose, PrepareMetricsData, SendMetricsData;

    protected $signature = 'eloquentize:property-aggregate-overall {model} {property} {aggregation} {--modelsPath=} {--scope=} {--scopeValue=} {--dry}';

    protected $description = 'Perform a sum of a property of a model for all time.';

    protected $verbose = false;

    public function perform(string $model, AggregationType $aggregation, string $property, $modelsPath = null, ?string $scope = null, ?string $scopeValue = null)
    {
        $metrics = [];
        $modelClass = $this->getModelClass($model, $modelsPath);

        if (! $this->isModelValid($modelClass, $property)) {
            return 1;
        }

        try {
            $method = $aggregation->value;
            // sound avg / min / max / sum return 0 if no records found ? for now
            $query = $modelClass::query();

            if ($scope) {
                if (method_exists($modelClass, 'scope'.$scope)) {
                    if ($scope && $scopeValue) {
                        $query = $query->$scope($scopeValue);
                    } elseif ($scope) {
                        $query = $query->$scope();
                    }
                } else {
                    $this->line("Scope $scope does not exist on model $model");
                }
            }

            $count = $query->$method($property) ?? 0;
            $this->verbose('The '.$method.' of '.$model.'->'.$property.' overall is : '.$count);

            $label = 'Overall '.$model;
            if ($scope && $scopeValue) {
                $label .= '::'.$scope.'('.$scopeValue.')';
            } elseif ($scope) {
                $label .= '::'.$scope;
            }
            $label .= '::'.$property.'->'.$method.'()';

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
        $modelsPath = $this->option('modelsPath');
        $scope = $this->option('scope');
        $scopeValue = $this->option('scopeValue');
        $oldestDate = $this->getOldestDateFromModel($model, $modelsPath);

        if ($scopeValue && ! $scope) {
            $this->error('"--scopeValue" option requires "--scope" option to be set.');

            return 1;
        }

        $metrics = $this->perform($model, $aggregation, $property, $modelsPath, $scope, $scopeValue);
        $period = new CarbonPeriod($oldestDate, Carbon::now()->endOfDay());
        $metricsData = $this->prepareMetricsData($metrics, $period, 'overall');

        $this->sendMetricsData($metricsData, env('ELOQUENTIZE_API_TOKEN'));

        return 0;

    }
}
