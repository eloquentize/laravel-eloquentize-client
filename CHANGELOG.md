# Changelog

All notable changes to `laravel-eloquentize-client` will be documented in this file.

04-11-2026

Add laravel 12 and 13 compatibility (tested against Laravel 10 to 13, PHP 8.1 to 8.4)

- `property-aggregate` and `property-aggregate-overall` no longer send anything to the API when the model or the property is invalid (an error code used to be sent as the metrics) and now exit with a failure code
- `property-aggregate` fails gracefully on an invalid date instead of throwing a `TypeError`
- An invalid aggregation type no longer calls `exit()`, which killed the whole host process: the commands now return a failure code
- Invalid dates are correctly detected with Carbon 3 (`createFromFormat` throws instead of returning `false`)
- `buildPeriod` returns `false` for an unhandled period type instead of using an undefined variable
- Tests no longer depend on mocks of the config repository, of the Artisan kernel or of protected methods

15-03-2024

Add laravel 11 compatibility

28-02-2024

Create a branch and a tag for old project, use dev-php7.4

23-02-2024

First commit, for alpha release

