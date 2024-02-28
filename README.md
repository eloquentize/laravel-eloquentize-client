# laravel lib for eloquentize.com service

[![Latest Version on Packagist](https://img.shields.io/packagist/v/eloquentize/laravel-eloquentize-client.svg?style=flat-square)](https://packagist.org/packages/eloquentize/laravel-eloquentize-client)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/eloquentize/laravel-eloquentize-client/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/eloquentize/laravel-eloquentize-client/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/eloquentize/laravel-eloquentize-client/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/eloquentize/laravel-eloquentize-client/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/eloquentize/laravel-eloquentize-client.svg?style=flat-square)](https://packagist.org/packages/eloquentize/laravel-eloquentize-client)

This is where your description should go. Limit it to a paragraph or two. Consider adding a small example.

## Support us

[<img src="https://alpha.eloquentize.com/images/eloquentize-logo-tr.svg" width="419px" />](https://alpha.eloquentize.com/docs)


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

# Getting start

Eloquentize offers a comprehensive monitoring solution for Laravel applications by providing a simple library integration, seamless API usage, and a customizable dashboard to visualize daily projects metrics. It enables efficient tracking and aggregation of model event metrics without additional coding, enhancing project management and insight.

### Requirements

At this point you need a laravel project, even if his version his very old ( 5.6 with php 7.4 is ok, mmm not really ok, because you might not use that anymore, but eloquentize will works on that. )

Having access to the shell might be a good thing to have, but it’s not mandatory, thanks to laravel scheduler. So you need to have a cron job activated on your server, if you use Laravel Forge just activate Laravel scheduler option.

You need table with timestamps, this is the point.

### Installation

The eloquentize library will be available soon on composer, as soon as the library will be available i assume you can do a composer require eloquentize/client

To set up Eloquentize, start by installing it through Composer with the following command:

```bash
composer require eloquentize/laravel-eloquentize-client
```

This should be done as soon as it becomes available. After installation, proceed to app.eloquentize.com to create your account and generate an API key. This key should then be added to your **`.env`** file in the following manner to complete the configuration process:

```makefile
ELOQUENTIZE_API_TOKEN=your_api_key_here
```

Eloquentize simplifies the integration into your Laravel projects by connecting through a library, utilizing an API for operations, and providing a dashboard for metrics visualization. This enables tracking of model events such as creation, update, and deletion efficiently without additional coding.

**Usage** involves executing artisan commands like the one below to gather daily metrics:

```bash
php artisan eloquentize:models-count
```

You can tailor the data collection to your needs by specifying dates, event types, or selecting specific models, with aggregation commands available for deeper insights. These commands can be automated by scheduling them within **`App\Console\Kernel`**.

For those with **existing projects**, historical data analysis from the project's inception is facilitated through:

```
php artisan eloquentize:models-count-legacy
```


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
