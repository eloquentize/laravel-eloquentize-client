<?php

namespace Eloquentize\LaravelClient\Commands\Traits;

trait HasVerbose
{
    protected function verbose($message, $level = 'info')
    {
        if ($this->verbose) {
            $this->$level($message);
        }
    }

    protected function defaultErrorMessage($message = 'An error occurred.')
    {
        $this->error($message.($this->verbose === false ? ' add -v for more details' : ''));
    }
}
