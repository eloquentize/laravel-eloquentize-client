<?php

namespace Eloquentize\LaravelClient\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BaseCommand extends Command
{
    protected $name = 'Eloquentize Base Command';

    public $defaultDateFormat = 'd/m/Y';

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (env('ELOQUENTIZE_API_TOKEN') === null) {
            throw new \Exception('ELOQUENTIZE_API_TOKEN is not set in .env');
        }

        if (env('APP_URL') === null) {
            throw new \Exception('app.url is not set in .env');
        }
        if (env('APP_ENV') === null) {
            throw new \Exception('app.env is not set in .env');
        }

        return parent::execute($input, $output);
    }
}
