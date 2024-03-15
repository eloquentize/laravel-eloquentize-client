# laravel lib for eloquentize.com service

[![Latest Version on Packagist](https://img.shields.io/packagist/v/eloquentize/laravel-eloquentize-client.svg?style=flat-square)](https://packagist.org/packages/eloquentize/laravel-eloquentize-client)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/eloquentize/laravel-eloquentize-client/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/eloquentize/laravel-eloquentize-client/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/eloquentize/laravel-eloquentize-client/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/eloquentize/laravel-eloquentize-client/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/eloquentize/laravel-eloquentize-client.svg?style=flat-square)](https://packagist.org/packages/eloquentize/laravel-eloquentize-client)

Eloquentize provides a full-featured monitoring toolkit for Laravel applications, designed for effortless integration. By incorporating a straightforward library, leveraging a smooth API, and offering a tailor-made dashboard, it simplifies the visualization of daily project metrics. This solution facilitates the efficient tracking and aggregation of model event metrics, streamlining project management and enriching insights, all without the need for extra coding.

[<img src="https://eloquentize.com/images/eloquentize-logo-tr.svg" width="128px" />](https://eloquentize.com/docs)

# Getting started

## Installation

You can install the package via composer:

```bash
composer require eloquentize/laravel-eloquentize-client
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="laravel-eloquentize-client-config"
```

## Usage

### Requirements

To get started, you'll require a Laravel project, regardless of its version; even older versions like 5.6 running on PHP 7.4 are compatible, although such setups are less common now. However, Eloquentize is designed to work seamlessly even on these versions.

Shell access is beneficial but not essential, thanks to Laravel's scheduler. Therefore, ensuring a cron job is set up on your server is necessary. If you're using Laravel Forge, simply enable the Laravel scheduler option.

Lastly, your database tables must include timestamps for Eloquentize to function correctly.

### Installation

To set up Eloquentize, start by installing it through Composer with the following command:

```bash
composer require eloquentize/laravel-eloquentize-client
```

for php 7.4 please use
```bash
composer require eloquentize/laravel-eloquentize-client dev-php7.4
```

After installation, proceed to [eloquentize](https://eloquentize.com) to create your account and generate an API key. This key should then be added to your **`.env`** file in the following manner to complete the configuration process:

```makefile
ELOQUENTIZE_API_TOKEN=your_api_key_here
```

**Usage** involves executing artisan commands like the one below to gather daily metrics:
( use --dry and -v for your test )
```bash
php artisan eloquentize:models-count --dry -v
```

You can tailor the data collection to your needs by specifying dates, event types, or selecting specific models, with aggregation commands available for deeper insights. These commands can be automated by scheduling them within **`App\Console\Kernel`**.

For those with **existing projects**, historical data analysis from the project's inception is facilitated through:

```
php artisan eloquentize:models-count-legacy
```
### Docs

The full doc can be found [Eloquentize](https://eloquentize.com/docs).

## Testing
```bash
pest
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

<!-- Please see [CONTRIBUTING](CONTRIBUTING.md) for details. -->

## Security Vulnerabilities

Please review [our security policy](security/policy) on how to report security vulnerabilities.

## Credits

- [alexis desmarais](https://github.com/eloquentize)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
