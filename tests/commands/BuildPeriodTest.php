<?php

use Carbon\CarbonPeriod;
use Eloquentize\LaravelClient\Mock\PeriodBuilder;

it('ensure BuildPeriod trait create a CarbonPeriod instance', function () {

    $periodBuilder = new PeriodBuilder;

    $date = '2021-01-01 00:00:00';
    $periodType = 'daily';
    $dateFormat = 'Y-m-d H:i:s';

    $period = $periodBuilder->buildPeriod($date, $periodType, $dateFormat);

    expect($period)->toBeInstanceOf(CarbonPeriod::class);
});
