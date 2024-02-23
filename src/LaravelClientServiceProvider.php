<?php

namespace Eloquentize\LaravelClient;

use Eloquentize\LaravelClient\Commands\ModelsCount;
use Eloquentize\LaravelClient\Commands\ModelsCountLegacy;
use Eloquentize\LaravelClient\Commands\PropertyAggregate;
use Eloquentize\LaravelClient\Commands\PropertyAggregateLegacy;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

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
            ->name('laravel-client')
            ->hasConfigFile('eloquentize')
            //->hasViews()
            //->hasMigration('create_laravel-client_table')
            ->hasCommand(ModelsCount::class)
            ->hasCommand(ModelsCountLegacy::class)
            ->hasCommand(PropertyAggregate::class)
            ->hasCommand(PropertyAggregateLegacy::class);
    }
}
