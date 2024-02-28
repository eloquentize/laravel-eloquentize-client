<?php

if (! function_exists('arch')) {
    return;
}

arch('it will not use debugging functions')
    ->expect(['dd', 'dump', 'ray'])
    ->each->not->toBeUsed();
