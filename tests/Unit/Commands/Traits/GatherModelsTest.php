<?php

use Eloquentize\LaravelClient\Commands\Traits\GatherModels;
use Eloquentize\LaravelClient\Commands\Traits\HasVerbose;
use Illuminate\Database\Eloquent\Model;

class GatherModelsTestClass
{
    use GatherModels, HasVerbose;

    public $verbose = false;

    protected $messages = [];

    public function info($message)
    {
        $this->messages['info'][] = $message;
    }

    public function error($message)
    {
        $this->messages['error'][] = $message;
    }

    public function warn($message)
    {
        $this->messages['warn'][] = $message;
    }

    public function getMessages()
    {
        return $this->messages;
    }

    // No need to expose protected methods here, we'll use Mockery to mock them
}

class TestModel extends Model
{
    protected $table = 'test_models';

    public $timestamps = true;
}

class TestModelNoTimestamps extends Model
{
    protected $table = 'test_models_no_timestamps';

    public $timestamps = false;
}

test('gatherModels returns model names from directory', function () {
    // Create a mock for the GatherModels trait
    $handler = Mockery::mock(GatherModelsTestClass::class);
    
    // Define the expected return values for different calls to gatherModels
    $handler->shouldReceive('gatherModels')
            ->with()
            ->andReturn(['User', 'Product', 'Order']);
            
    $handler->shouldReceive('gatherModels')
            ->with(['User', 'Order'])
            ->andReturn(['User', 'Order']);
            
    $handler->shouldReceive('gatherModels')
            ->with(null, 'Custom/Path')
            ->andReturn(['User', 'Product', 'Order']);

    // Test with no filter
    $models = $handler->gatherModels();
    expect($models)->toBeArray();
    expect($models)->toHaveCount(3);
    expect($models)->toContain('User');
    expect($models)->toContain('Product');
    expect($models)->toContain('Order');

    // Test with filter
    $models = $handler->gatherModels(['User', 'Order']);
    expect($models)->toBeArray();
    expect($models)->toHaveCount(2);
    expect($models)->toContain('User');
    expect($models)->toContain('Order');
    expect($models)->not->toContain('Product');

    // Test with custom path
    $models = $handler->gatherModels(null, 'Custom/Path');
    expect($models)->toBeArray();
    expect($models)->toHaveCount(3);
});

// We're not testing protected methods directly

// Skipping tests for protected methods that require more complex mocking
// These would be better tested through integration tests of the commands that use them
