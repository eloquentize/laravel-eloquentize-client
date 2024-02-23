<?php

namespace Eloquentize\LaravelClient\Commands;

use Eloquentize\LaravelClient\Commands\Traits\BuildPeriod;
use Eloquentize\LaravelClient\Commands\Traits\GatherModels;
use Eloquentize\LaravelClient\Commands\Traits\HasVerbose;
use Eloquentize\LaravelClient\Commands\Traits\ModelsOption;
use Illuminate\Support\Carbon;

use function Laravel\Prompts\progress;

class ModelsCountLegacy extends BaseCommand
{
    use BuildPeriod, GatherModels, HasVerbose, ModelsOption;

    protected $signature = 'eloquentize:models-count-legacy {date?} {--event=created_at} {--periodType=daily} {--dateFormat=} {--M|models=} {--modelsPath=}';

    protected $description = 'Send to Eloquentize the counts of all models from a given date or from the oldest eloquent model created_at to yesterday.';

    protected $verbose = false;

    public function handle()
    {

        $this->verbose = $this->option('verbose') ?? false;
        $dateFormat = $this->option('dateFormat') ?? $this->defaultDateFormat;
        $event = $this->option('event') ?? 'created_at';
        $modelsPath = $this->option('modelsPath');
        $filteredModels = $this->parseModelsOption($this->option('models'));
        $oldestDate = $this->getOldestDateFromModels($this->gatherModels($filteredModels, $modelsPath), $modelsPath);

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

        if ($date == null) {
            $this->error('No Records found.');

            return 1;
        }
        $this->verbose("date format: $dateFormat");
        $dateRange = $this->getArrayOfDays($date);
        $this->verbose("Gather metrics from: $date");

        // find a way to use the progress bar only if laravel version is >8 disabled for now
        //$progress = progress(label: 'Processing date range', steps: count($dateRange));
        //$progress->start();

        foreach ($dateRange as $d) {
            // Format the date into a string that the command will understand
            $dateString = $d->format($dateFormat);
            $this->verbose("Calling eloquentize:models-count for $dateString with format $dateFormat.");

            // Call the command with the required parameters
            $this->call('eloquentize:models-count', [
                'date' => $dateString,
                '-v' => true,
                '--event' => $event,
                '--modelsPath' => $modelsPath,
                '--dateFormat' => $dateFormat,
            ]);

            // Advance the progress bar after processing each date
            //$progress->advance();
        }

        // Finish the progress bar after processing all dates
        //$progress->finish();
        return 0;
    }
}
