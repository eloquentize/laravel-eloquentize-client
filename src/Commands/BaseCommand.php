<?php

namespace Eloquentize\LaravelClient\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BaseCommand extends Command
{
    protected $name = 'Eloquentize Base Command';

    public $defaultDateFormat = 'd/m/Y';

    protected function execute(InputInterface $input, OutputInterface $output) : int
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
        if (env('APP_URL') === 'http://localhost') {
            throw new \Exception('app.url is set to http://localhost. Please change these values in .env bevause eloquentize identifies the environment by the app.url and app.env values.');
        }

        return parent::execute($input, $output);
    }

    protected function cleanAppUrl($url) : string
    {
        $url = preg_replace('#^https?://#', '', $url);
        $url = preg_replace('#^http?://#', '', $url);
        $url = rtrim($url, '/');

        return $url;
    }
}
