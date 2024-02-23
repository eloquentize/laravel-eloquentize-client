<?php

namespace Eloquentize\LaravelClient\Commands\Traits;

trait ModelsOption
{
    public function parseModelsOption(?string $modelsOption)
    {
        if ($modelsOption !== null) {
            return explode(',', $modelsOption);
        } else {
            return null;
        }
    }
}
