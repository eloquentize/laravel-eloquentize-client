<?php

use Eloquentize\LaravelClient\Commands\Traits\HasVerbose;

class HasVerboseTestClass
{
    use HasVerbose;

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

    public function warning($message)
    {
        $this->messages['warning'][] = $message;
    }

    public function getMessages()
    {
        return $this->messages;
    }

    public function testVerbose($message, $level = 'info')
    {
        $this->verbose($message, $level);
    }

    public function testDefaultErrorMessage($message = 'An error occurred.')
    {
        $this->defaultErrorMessage($message);
    }
}

test('verbose does not output message when verbose is false', function () {
    $handler = new HasVerboseTestClass;
    $handler->verbose = false;

    $handler->testVerbose('Test message');

    expect($handler->getMessages())->toBeEmpty();
});

test('verbose outputs info message when verbose is true', function () {
    $handler = new HasVerboseTestClass;
    $handler->verbose = true;

    $handler->testVerbose('Test info message');

    expect($handler->getMessages()['info'])->toContain('Test info message');
});

test('verbose outputs warning message when level is warning', function () {
    $handler = new HasVerboseTestClass;
    $handler->verbose = true;

    $handler->testVerbose('Test warning message', 'warning');

    expect($handler->getMessages()['warning'])->toContain('Test warning message');
});

test('defaultErrorMessage includes verbose hint when verbose is false', function () {
    $handler = new HasVerboseTestClass;
    $handler->verbose = false;

    $handler->testDefaultErrorMessage('Custom error');

    expect($handler->getMessages()['error'][0])->toBe('Custom error add -v for more details');
});

test('defaultErrorMessage does not include verbose hint when verbose is true', function () {
    $handler = new HasVerboseTestClass;
    $handler->verbose = true;

    $handler->testDefaultErrorMessage('Custom error');

    expect($handler->getMessages()['error'][0])->toBe('Custom error');
});

test('defaultErrorMessage uses default message when none provided', function () {
    $handler = new HasVerboseTestClass;
    $handler->verbose = false;

    $handler->testDefaultErrorMessage();

    expect($handler->getMessages()['error'][0])->toBe('An error occurred. add -v for more details');
});
