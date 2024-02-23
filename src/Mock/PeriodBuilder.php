<?php

namespace Eloquentize\LaravelClient\Mock;

use Eloquentize\LaravelClient\Commands\Traits\BuildPeriod;

class PeriodBuilder
{
    use BuildPeriod;

    public function error($message)
    {
        // do nothing
    }

    public function info($message)
    {
        // do nothing
    }
}
