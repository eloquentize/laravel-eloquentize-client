<?php

namespace Eloquentize\LaravelClient\Tests;

use Database\Factories\UserFactory;
use Orchestra\Testbench\TestCase as Orchestra;
use Illuminate\Database\Eloquent\Factories\Factory;
use Eloquentize\LaravelClient\LaravelClientServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Database\\Factories\\'.class_basename($modelName).'Factory'
        );
        
    }

    protected function getPackageProviders($app)
    {
        return [
            LaravelClientServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        app()->useAppPath(__DIR__.'/../src/app');
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        $migration = include __DIR__.'/../database/migrations/create_users_table.php.stub';
        $migration->up();
        $migration = include __DIR__.'/../database/migrations/create_bills_table.php.stub';
        $migration->up();
        
    }
}
