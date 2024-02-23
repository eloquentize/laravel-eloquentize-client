<?php

namespace Eloquentize\LaravelClient\Commands\Traits;

trait DateArgument
{
    protected function resolveDate(string $dateArgument)
    {
        switch ($dateArgument) {
            case 'today':
                return now()->format($this->defaultDateFormat);
            case 'yesterday':
                return now()->subDay()->format($this->defaultDateFormat);
            default:
                return $dateArgument;
        }
    }
}
