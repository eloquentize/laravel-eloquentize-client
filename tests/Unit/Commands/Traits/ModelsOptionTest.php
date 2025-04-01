<?php

use Eloquentize\LaravelClient\Commands\Traits\ModelsOption;

class ModelsOptionTestClass
{
    use ModelsOption;

    public function testParseModelsOption($modelsOption)
    {
        return $this->parseModelsOption($modelsOption);
    }
}

test('parseModelsOption returns null for null input', function () {
    $handler = new ModelsOptionTestClass;
    $models = $handler->testParseModelsOption(null);

    expect($models)->toBeNull();
});

test('parseModelsOption returns array for single model', function () {
    $handler = new ModelsOptionTestClass;
    $models = $handler->testParseModelsOption('User');

    expect($models)->toBeArray();
    expect($models)->toHaveCount(1);
    expect($models[0])->toBe('User');
});

test('parseModelsOption returns array for multiple models', function () {
    $handler = new ModelsOptionTestClass;
    $models = $handler->testParseModelsOption('User,Product,Order');

    expect($models)->toBeArray();
    expect($models)->toHaveCount(3);
    expect($models[0])->toBe('User');
    expect($models[1])->toBe('Product');
    expect($models[2])->toBe('Order');
});

test('parseModelsOption handles whitespace', function () {
    $handler = new ModelsOptionTestClass;
    $models = $handler->testParseModelsOption('User, Product, Order');

    expect($models)->toBeArray();
    expect($models)->toHaveCount(3);
    expect($models[0])->toBe('User');
    expect($models[1])->toBe(' Product');
    expect($models[2])->toBe(' Order');
});
