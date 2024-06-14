<?php

namespace Eloquentize\LaravelClient;

use Spatie\LaravelPackageTools\Package;
use Eloquentize\LaravelClient\Commands\ModelsCount;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Eloquentize\LaravelClient\Commands\ModelCountOverall;
use Eloquentize\LaravelClient\Commands\ModelsCountLegacy;
use Eloquentize\LaravelClient\Commands\PropertyAggregate;
use Eloquentize\LaravelClient\Commands\PropertyAggregateLegacy;
use Eloquentize\LaravelClient\Commands\PropertyAggregateOverall;

class LaravelClientServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-eloquentize-client')
            ->hasConfigFile('eloquentize')
            //->hasViews()
            //->hasMigration('create_laravel-client_table')
            ->hasCommand(ModelsCount::class)
            ->hasCommand(ModelsCountLegacy::class)
            ->hasCommand(ModelCountOverall::class)
            ->hasCommand(PropertyAggregate::class)
            ->hasCommand(PropertyAggregateLegacy::class)
            ->hasCommand(PropertyAggregateOverall::class);
    }
}
