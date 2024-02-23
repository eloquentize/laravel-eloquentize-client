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

    protected $signature = 'eloquentize:models-count {date?} {--event=created_at} {--periodType=daily} {--dateFormat=} {--M|models=} {--modelsPath=}';

    protected $description = 'Send to Eloquentize the counts of all models for a given date and event.';

    protected $verbose = false;

    public function performModelCount(array $models, CarbonPeriod $period, string $event, ?string $modelsPath = null)
    {
        $metrics = [];
        foreach ($models as $model) {
            $modelClass = $this->getModelClass($model, $modelsPath);

            if (! $this->isModelValid($modelClass, $event)) {
                continue;
            }

            $count = $modelClass::whereBetween($event, [$period->getStartDate(), $period->getEndDate()])->count();
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
        $filteredModels = $this->parseModelsOption($this->option('models'));

        $period = $this->buildPeriod($date, $periodType, $dateFormat);
        $models = $this->gatherModels($filteredModels, $modelsPath);

        if (count($models) < 1) {
            $this->error('No models found.');

            return 1;
        }

        $metrics = $this->performModelCount($models, $period, $event, $modelsPath);
        $metricsData = $this->prepareMetricsData($metrics, $period, $event);

        $this->verbose('Sending models count data to eloquentize...'.config('eloquentize.api_url').'/api/metrics/models');
        $this->sendMetricsData($metricsData, env('ELOQUENTIZE_API_TOKEN'), $event);

        $this->line('Models count data sent to eloquentize.');

        return 0;
    }
}
