<?php

namespace Eloquentize\LaravelClient\Commands\Traits;

use Carbon\CarbonPeriod;

trait PrepareMetricsData
{
    protected function prepareMetricsData($metrics, CarbonPeriod $period, $event)
    {
        return [
            //'team_id' => $this->getTeamId(), // Assume this method retrieves the team ID, possibly from a config or environment variable
            'start' => $period->getStartDate(),
            'end' => $period->getEndDate(),
            'timezone' => config('app.timezone') ?? 'UTC',
            'app_url' => config('app.url'),
            'env' => config('app.env'),
            'event_type' => $event,
            'metrics' => $metrics,
            // 'comment' => 'Optional comment or additional data can be added here if needed.'
        ];
    }
}
