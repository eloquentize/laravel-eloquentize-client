<?php

namespace Eloquentize\LaravelClient\Commands;

use Carbon\CarbonPeriod;
use Illuminate\Support\Carbon;
use Eloquentize\LaravelClient\Commands\Traits\HasVerbose;
use Eloquentize\LaravelClient\Commands\Traits\BuildPeriod;
use Eloquentize\LaravelClient\Commands\Traits\DateArgument;
use Eloquentize\LaravelClient\Commands\Traits\GatherModels;
use Eloquentize\LaravelClient\Commands\Traits\ModelsOption;
use Eloquentize\LaravelClient\Commands\Traits\SendMetricsData;
use Eloquentize\LaravelClient\Commands\Traits\PrepareMetricsData;

class ModelCountOverall extends BaseCommand
{
    use BuildPeriod, DateArgument, GatherModels, HasVerbose, ModelsOption, PrepareMetricsData, SendMetricsData;

    protected $signature = 'eloquentize:model-count-overall {model} {--modelsPath=} {--scope=} {--scopeValue=} {--dry} ';

    protected $description = 'Send to Eloquentize the counts of all models for a given date and event.';

    protected $verbose = false;

    protected $dry = false;

    public function perform(string $model, $modelsPath = null, ?string $scope = null, ?string $scopeValue = null)
    {
        $metrics = [];
        $modelClass = $this->getModelClass($model, $modelsPath);


        try {

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

            $count = $query->count();
            $this->verbose('The count of model '.$model.' overall is : '.$count);

            $label = 'Overall '.$model;
            if ($scope && $scopeValue) {
                $label .= '::'.$scope.'('.$scopeValue.')';
            } elseif ($scope) {
                $label .= '::'.$scope;
            }

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
        $modelsPath = $this->option('modelsPath');
        $scope = $this->option('scope');
        $scopeValue = $this->option('scopeValue');
        $oldestDate = $this->getOldestDateFromModel($model, $modelsPath);

        if ($scopeValue && ! $scope) {
            $this->error('"--scopeValue" option requires "--scope" option to be set.');

            return 1;
        }

        $metrics = $this->perform($model, $modelsPath, $scope, $scopeValue);
        $period = new CarbonPeriod($oldestDate, Carbon::now()->endOfDay());
        $metricsData = $this->prepareMetricsData($metrics, $period, 'overall');

        $this->sendMetricsData($metricsData, env('ELOQUENTIZE_API_TOKEN'));

        return 0;
    }
}
