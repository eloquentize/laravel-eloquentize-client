<?php

namespace Eloquentize\LaravelClient\Commands;

use Carbon\CarbonPeriod;
use Eloquentize\LaravelClient\Commands\Traits\BuildPeriod;
use Eloquentize\LaravelClient\Commands\Traits\DateArgument;
use Eloquentize\LaravelClient\Commands\Traits\GatherModels;
use Eloquentize\LaravelClient\Commands\Traits\HasVerbose;
use Eloquentize\LaravelClient\Commands\Traits\ModelsOption;
use Eloquentize\LaravelClient\Commands\Traits\PrepareMetricsData;
use Eloquentize\LaravelClient\Commands\Traits\SendMetricsData;

class ModelsCount extends BaseCommand
{
    use BuildPeriod, DateArgument, GatherModels, HasVerbose, ModelsOption, PrepareMetricsData, SendMetricsData;

    protected $signature = 'eloquentize:models-count {date?} {--event=created_at} {--periodType=daily} {--dateFormat=} {--M|models=} {--modelsPath=} {--scope=} {--scopeValue=} ';

    protected $description = 'Send to Eloquentize the counts of all models for a given date and event.';

    protected $verbose = false;

    public function performModelCount(array $models, CarbonPeriod $period, string $event, ?string $modelsPath = null, ?string $scope = null, ?string $scopeValue = null)
    {
        $metrics = [];
        foreach ($models as $model) {
            $modelClass = $this->getModelClass($model, $modelsPath);

            if (! $this->isModelValid($modelClass, $event)) {
                continue;
            }
            $query = $modelClass::whereBetween($event, [$period->getStartDate(), $period->getEndDate()]);
            // check if the model has corresponding scope and apply it
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
            $this->verbose("Counting $model - count: ".$count);
            $metrics[] = (object) ['label' => $model, 'count' => $count];

        }

        return $metrics;
    }

    public function handle()
    {
        $this->verbose = $this->option('verbose') ?? false;
        $date = $this->resolveDate($this->argument('date') ?? 'today');
        $event = $this->option('event') ?? 'created_at';
        $periodType = $this->option('periodType') ?? 'daily';
        $dateFormat = $this->option('dateFormat') ?? $this->defaultDateFormat;
        $modelsPath = $this->option('modelsPath');
        $scope = $this->option('scope');
        $scopeValue = $this->option('scopeValue');
        $filteredModels = $this->parseModelsOption($this->option('models'));

        if ($scope && ! $filteredModels) {
            $this->error('"scope" option requires "--models" option to be set. models provided should have a corresponding scope.');

            return 1;
        }

        if ($scopeValue && ! $scope) {
            $this->error('"--scopeValue" option requires "--scope" option to be set.');

            return 1;
        }

        $period = $this->buildPeriod($date, $periodType, $dateFormat);
        $models = $this->gatherModels($filteredModels, $modelsPath);

        if (count($models) < 1) {
            $this->error('No models found.');

            return 1;
        }

        $metrics = $this->performModelCount($models, $period, $event, $modelsPath, $scope, $scopeValue);
        $metricsData = $this->prepareMetricsData($metrics, $period, $event);

        $this->verbose('Sending models count data to eloquentize...'.config('eloquentize.api_url').'/api/metrics/models');
        $this->sendMetricsData($metricsData, env('ELOQUENTIZE_API_TOKEN'), $event);

        $this->line('Models count data sent to eloquentize.');

        return 0;
    }
}
