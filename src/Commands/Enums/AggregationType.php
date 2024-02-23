<?php

namespace Eloquentize\LaravelClient\Commands\Enums;

enum AggregationType: string
{
    case sum = 'sum';
    case avg = 'avg';
    case max = 'max';
    case min = 'min';
}
