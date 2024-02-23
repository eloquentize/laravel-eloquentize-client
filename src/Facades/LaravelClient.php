<?php

namespace Eloquentize\LaravelClient\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Eloquentize\LaravelClient\LaravelClient
 */
class LaravelClient extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Eloquentize\LaravelClient\LaravelClient::class;
    }
}
