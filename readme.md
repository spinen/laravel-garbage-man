# SPINEN's Laravel Garbage Man

[![Latest Stable Version](https://poser.pugx.org/spinen/laravel-garbage-man/v/stable)](https://packagist.org/packages/spinen/laravel-garbage-man)
[![Latest Unstable Version](https://poser.pugx.org/spinen/laravel-garbage-man/v/unstable)](https://packagist.org/packages/spinen/laravel-garbage-man)
[![Total Downloads](https://poser.pugx.org/spinen/laravel-garbage-man/downloads)](https://packagist.org/packages/spinen/laravel-garbage-man)
[![License](https://poser.pugx.org/spinen/laravel-garbage-man/license)](https://packagist.org/packages/spinen/laravel-garbage-man)

The soft deletes are great in Laravel to make sure that some deleted data can be recovered. This package allows you to configure an array of models with how many days that you want the soft deleted data to stay in the database.

## Build Status

| Branch | Status | Coverage | Code Quality |
| ------ | :----: | :------: | :----------: |
| Develop | [![Build Status](https://github.com/spinen/laravel-garbage-man/workflows/CI/badge.svg?branch=develop)](https://github.com/spinen/laravel-garbage-man/workflows/CI/badge.svg?branch=develop) | [![Code Coverage](https://scrutinizer-ci.com/g/spinen/laravel-garbage-man/badges/coverage.png?b=develop)](https://scrutinizer-ci.com/g/spinen/laravel-garbage-man/?branch=develop) | [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/spinen/laravel-garbage-man/badges/quality-score.png?b=develop)](https://scrutinizer-ci.com/g/spinen/laravel-garbage-man/?branch=develop) |
| Master | [![Build Status](https://github.com/spinen/laravel-garbage-man/workflows/CI/badge.svg?branch=master)](https://github.com/spinen/laravel-garbage-man/workflows/CI/badge.svg?branch=master) | [![Code Coverage](https://scrutinizer-ci.com/g/spinen/laravel-garbage-man/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/spinen/laravel-garbage-man/?branch=master) | [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/spinen/laravel-garbage-man/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/spinen/laravel-garbage-man/?branch=master) |

## Prerequisite

#### NOTE: If you need to use < PHP 7.2, please stay with version 1.x

As side from Laravel >= 5.1.10 (5.1.10 is the first version that had the warn method, so that is the minimum for logging), there is 1 package that is required.

* [nesbot/carbon](https://github.com/briannesbitt/Carbon)

## Install

Install Garbage Man:

```bash
$ composer require spinen/laravel-garbage-man
```

### For >= Laravel 5.5, you are done with the installation

The package uses the [auto registration feature](https://laravel.com/docs/5.8/packages#package-discovery) of Laravel 5.

## Upgrading to 2.x from 1.x

As of Laravel 5.8 (and deprecated in 5.4), the `fire()` method on the dispatcher contract [was removed](https://laravel.com/docs/5.8/upgrade) in favor of `dispatch()`. Therefore, we have updated our code to use `dispatch()`.  You will need to change `fire()` to `dispatch()` in your `config/garbageman.php` file.

## Using the command

The command is registered with laravel as ```garbageman:purge```.  You can run it one of 2 ways...

1. from the command line ```php artisan garbageman:purge;```
2. via a scheduled task.

To automatically run the script as a scheduled job, then add the following to the schedule method of
`App\Console\Kernel.php`:

```php
$schedule->command('garbageman:purge')
         ->daily();
```

You can use whatever schedule that you need to keep the records purged out. Just review the list at
[http://laravel.com/docs/master/scheduling#schedule-frequency-options](http://laravel.com/docs/master/scheduling#schedule-frequency-options).

You can also use any of the advanced configuration options of the task scheduler like "Task Output" or "Task Hooks" as
listed on the [Laravel documentation](http://laravel.com/docs/master/scheduling).

## Configuration

Publish the package config file to `config/garbageman.php`:

```bash
$ php artisan vendor:publish
```

This file is fully documented. You will need to make the changes to that file to suit your needs. There are 3 main configuration items...

1. Dispatch purge events - Dispatch events on purge of each record.
2. Logging level - Level to log.
3. Schedule - Models & number of days to allow the soft deleted record to stay before purging.

### Dispatch purge events (dispatch\_purge\_events)

You may hook into the purge of each record by throwing events before & after deleting of each record. There are 2 events thrown:

* garbageman.purging:\<full/model/name\>
* garbageman.purged:\<full/model/name\>

The model is passed with each of the events. The "purging" event is thrown just *before* the actual delete & "purged" is thrown just *after* the actual deletion.

**Please note:** This is an expensive operation as it requires a SQL command for each record to delete so that the record can be thrown with the events. Therefore, unless you need to catch the events to perform some other action, leave this false to allow all records per model to get deleted with a single SQL call.

### Logging level (logging_level)

The level that log messages are generated, which will display information on the console output and in the logs.

| Level | Description |
| :---: | ----------- |
| 0 | Emergency: system is unusable |
| 1 | Alert: action must be taken immediately |
| 2 | Critical: critical conditions |
| 3 | Error: error conditions |
| 4 | Warning: warning conditions |
| 5 | Notice: normal but significant condition |
| 6 (default) | Info: informational messages |
| 7 | Debug: debug - level messages |

There is a key for the console & one for the log. Here is an example...

```php
'logging_level' => [
    'console' => 3,
    'log'     => 6,
],
```

Alternatively, you can set the levels with environmental variables ```GARBAGEMAN_CONSOLE_LOG_LEVEL``` and ```GARBAGEMAN_LOG_LEVEL```.

### Schedule (schedule)

The age is in days for each model. Here is an example...

```php
'schedule' => [
    App\ModelOne::class => 14,
    App\ModelTwo::class => 30,
],
```
This would purge any ModelOnes that were deleted over 14 days ago and any ModelTwos that were deleted over 30 days ago.
