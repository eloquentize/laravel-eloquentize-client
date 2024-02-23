<?php

namespace Eloquentize\LaravelClient\Commands\Traits;

use Carbon\CarbonPeriod;
use Illuminate\Support\Carbon;

trait BuildPeriod
{
    public function buildPeriod(&$date, $periodType, $dateFormat)
    {
        if (! in_array($periodType, ['daily'])) {
            $this->error("Invalid periodType given. It should be either 'daily' for now.");

            return false;
        }

        if ($date !== null) {
            $carbonDate = Carbon::createFromFormat($dateFormat, $date);

            if ($date === false || $carbonDate->format($dateFormat) !== $date) {
                $this->error("Invalid date format. The date should be formatted according to the provided date format: '".$dateFormat."'.");
                $this->error('Given date: '.$date);

                return false;
            }

            switch ($periodType) {
                // case $periodType === 'hourly':
                //     $period = new CarbonPeriod($carbonDate->copy()->startOfHour(), $carbonDate->copy()->endOfHour());
                //     break;
                case $periodType === 'daily':
                    $period = new CarbonPeriod($carbonDate->copy()->startOfDay(), $carbonDate->copy()->endOfDay());
                    break;
                default:
                    $this->error("unhandled periodType: $periodType");
                    break;
            }

            $this->info('Period: '.$period->getStartDate()->format($dateFormat.' H:i:s').' to '.$period->getEndDate()->format($dateFormat.' H:i:s').' - '.config('app.timezone'));

            return $period;
        }

        $this->error('Period could not be built. with date: '.$date.' periodType: '.$periodType.' dateFormat: '.$dateFormat);

        return false;
    }

    public function getArrayOfDays(Carbon $oldestDate)
    {
        //This is if you want to get the previous day from today
        $yesterday = Carbon::now()->subDay();

        $dateRange = [];

        for ($date = $oldestDate; $date->lte($yesterday); $date->addDay()) {
            $dateRange[] = $date->copy(); // copy the instance to keep the original unchanged
        }

        $dateRange[] = $yesterday;

        return $dateRange;
    }
}
