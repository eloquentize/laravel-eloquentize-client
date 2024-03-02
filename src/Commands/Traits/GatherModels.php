<?php

namespace Eloquentize\LaravelClient\Commands\Traits;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

trait GatherModels
{
    public function gatherModels($filterModels = null, ?string $modelsPath = null): array
    {
        if ($modelsPath !== null) {
            $modelsDirectory = app_path($modelsPath);
        } else {
            $modelsDirectory = app_path('Models/');
            if (!is_dir($modelsDirectory)) {
                $modelsDirectory = app_path('/');
            }
        }

        $files = scandir($modelsDirectory);
        $models = [];
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            // Full path to the file
            $filePath = $modelsDirectory . $file;
            // Check if it's a file, not a directory
            // ALX :: we don't need to check if it's a file on .php
            // if (!is_file($filePath)) {
            //     continue;
            // }
            // Optionally, ensure the file has a .php extension
            if (pathinfo($filePath, PATHINFO_EXTENSION) !== 'php') {
                continue;
            }
            $modelName = pathinfo($file, PATHINFO_FILENAME);
            if ($filterModels !== null && !in_array($modelName, $filterModels)) {
                continue;
            }
            $models[] = $modelName;
        }

        return $models;
    }


    public function getOldestDateFromModels(array $models, ?string $modelsPath = null)
    {
        $earliestDate = null;

        foreach ($models as $model) {
            $modelClass = $this->getModelClass($model, $modelsPath);

            if (! $this->isModelValid($modelClass, 'created_at')) {
                continue;
            }

            // Perform a query to get the earliest 'created_at' record for the current table
            $date = (new $modelClass)->min('created_at');

            // If no date was found, skip to the next iteration
            if (! $date) {
                continue;
            }

            $date = Carbon::parse($date);

            // Update $earliestDate if this date is earlier than the current earliest
            if (! $earliestDate || $date->lt($earliestDate)) {
                $earliestDate = $date;
            }
        }

        return $earliestDate;
    }

    public function getOldestDateFromModel(string $model, ?string $modelsPath = null)
    {
        $modelClass = $this->getModelClass($model, $modelsPath);

        if (! $this->isModelValid($modelClass, 'created_at')) {
            return null;
        }

        // Perform a query to get the earliest 'created_at' record for the current table
        $date = (new $modelClass)->min('created_at');

        // If no date was found, return null
        if (! $date) {
            $this->error("No record found for $model");

            return 1;
        }

        return Carbon::parse($date);
    }

    /**
     * TODO, handle custom model directory
     */
    protected function getModelClass(string $model, $modelsPath = null): string
    {
        // Default models directory inside the app folder
        $defaultModelsDirectory = app_path('Models/');

        // Check if a custom models path is provided
        if ($modelsPath !== null) {
            // Validate if the custom path is a directory
            if (! is_dir(app_path($modelsPath))) {
                throw new \Exception("The provided models path is not a valid directory: {$modelsPath}");
            }

            // Use the custom models path to determine the namespace
            $namespace = str_replace('/', '\\', $modelsPath); // Convert path to namespace
            //return namespace; // Ensure the namespace is properly formatted

            return "App\\{$namespace}\\{$model}";
        }

        // Fall back to default behavior if no custom path is provided
        if (! is_dir($defaultModelsDirectory)) {
            return "App\\{$model}";
        }

        return "App\\Models\\{$model}";
    }

    protected function isModelValid(string $modelClass, string $column): bool
    {

        if (! class_exists($modelClass)) {
            $this->defaultErrorMessage();
            $this->verbose("Model class $modelClass did not exists.", 'warn');

            return false;
        }

        try {
            $instance = new $modelClass;
        } catch (\Throwable $e) {
            $this->defaultErrorMessage();
            $this->verbose("Model class $modelClass is not instanciable.", 'warn');

            return false;
        }

        if (! $instance instanceof \Illuminate\Database\Eloquent\Model) {
            $this->defaultErrorMessage();
            $this->verbose("Model class $modelClass is not an instance of \Illuminate\Database\Eloquent\Model.", 'warn');

            return false;
        }

        if (! $instance->usesTimestamps()) {
            $this->defaultErrorMessage();
            $this->verbose("Model class $modelClass does not use timestamps.", 'warn');

            return false;
        }

        if (! Schema::connection($instance->getConnectionName())->hasColumn($instance->getTable(), $column)) {
            $this->defaultErrorMessage();
            $this->verbose("Model class $modelClass does not have a column named ".$column, 'warn');

            return false;
        }

        return true;
    }
}
