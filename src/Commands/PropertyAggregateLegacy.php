<?php

namespace Eloquentize\LaravelClient\Commands;

use Eloquentize\LaravelClient\Commands\Traits\AggregationArgument;
use Eloquentize\LaravelClient\Commands\Traits\BuildPeriod;
use Eloquentize\LaravelClient\Commands\Traits\GatherModels;
use Eloquentize\LaravelClient\Commands\Traits\HasVerbose;
use Eloquentize\LaravelClient\Commands\Traits\ModelsOption;
use Illuminate\Support\Carbon;

use function Laravel\Prompts\progress;

class PropertyAggregateLegacy extends BaseCommand
{
    use AggregationArgument, BuildPeriod, GatherModels, HasVerbose, ModelsOption;

    protected $signature = 'eloquentize:property-aggregate-legacy {model} {property} {aggregation} {date?} {event?} {--periodType=daily} {--dateFormat=} {--modelsPath=}';

    protected $description = 'Send to Eloquentize the aggregation of a model property from a given date or from the oldest eloquent model created_at to yesterday';

    protected $verbose = false;

    public function handle()
    {
        $this->verbose = $this->option('verbose') ?? false;
        $dateFormat = $this->option('dateFormat') ?? $this->defaultDateFormat;
        $model = $this->argument('model');
        $property = $this->argument('property');
        $aggregation = $this->resolveAggregation($this->argument('aggregation'));
        $event = $this->argument('event') ?? 'created_at';
        $modelsPath = $this->option('modelsPath');
        $oldestDate = $this->getOldestDateFromModel($model, $modelsPath);

        if ($this->argument('date')) {
            try {
                $date = Carbon::createFromFormat($dateFormat, $this->argument('date'));
            } catch (\Exception $e) {
                $this->error('Invalid date format. The date should be formatted according to the provided date format: '.$dateFormat);
                $this->error('Given date: '.$this->argument('date'));

                return 1;
            }
        } else {
            $date = $oldestDate;
        }

        $this->verbose("date format: $dateFormat");
        $dateRange = $this->getArrayOfDays($date);
        info(print_r($dateRange, true));
        $this->verbose("Gather metrics from: $date");

        // find a way to use the progress bar only if laravel version is >8 disabled for now
        //$progress = progress(label: 'Processing date range', steps: count($dateRange));
        //$progress->start();

        foreach ($dateRange as $d) {
            // Format the date into a string that the command will understand
            $dateString = $d->format($dateFormat);
            $this->verbose("Calling eloquentize:property-aggregate $aggregation->value for $dateString with format $dateFormat.");

            // Call the command with the required parameters
            $this->call('eloquentize:property-aggregate', [
                'model' => $model,
                'property' => $property,
                'aggregation' => $aggregation->value,
                'date' => $dateString,
                '-v' => true,
                '--event' => $event,
                '--dateFormat' => $dateFormat,
                '--modelsPath' => $modelsPath,
            ]);

            // Advance the progress bar after processing each date
            //$progress->advance();
        }

        // Finish the progress bar after processing all dates
        //$progress->finish();
        return 0;
    }
}
