# SPINEN's Laravel Garbage Man

The soft deletes are great in laravel to make sure that some deleted data can be recovered.  This package, allows you to
configure an array of models with how many days that you want the soft deleted data to stay in the database.

## Build Status

| Branch | Status |
| ------ | :----: |
| Develop | [![Build Status](https://ci.spinen.net/buildStatus/icon?job=laravel-garbage-man-develop)](https://ci.spinen.net/view/Libraries/job/laravel-garbage-man-develop/) |
| Feature | [![Build Status](https://ci.spinen.net/buildStatus/icon?job=laravel-garbage-man-feature__)](https://ci.spinen.net/view/Libraries/job/laravel-garbage-man-feature__/) |
| Master | [![Build Status](https://ci.spinen.net/buildStatus/icon?job=laravel-garbage-man-master)](https://ci.spinen.net/view/Libraries/job/laravel-garbage-man-master/) |
| Release | [![Build Status](https://ci.spinen.net/buildStatus/icon?job=laravel-garbage-man-release__)](https://ci.spinen.net/view/Libraries/job/laravel-garbage-man-release__/) |

## Prerequisite

As side from Laravel 5.x, there is 1 package that is required

* nesbot/carbon

## Install

Install Garbage Man:

```bash
$ composer require spinen/laravel-garbage-man
```

Add the Service Provider to `config/app.php`:

```php
'providers' => [
    // ...
    Spinen\GarbageMan\GarbageManServiceProvider::class,
];
```

Publish the package config file to `config/garbageman.php`:

```bash
$ php artisan vendor:publish
```

## Using the command

The command is registered with laravel as ```garbagemand:purge```.  You can run it one of 2 ways...

1) from the command line ```php artisan garbageman:purge;```
2) via scheduled task.

To automatically run the script as a scheduled job, then add the following to the schedule method of App\Console\Kernel.php:

```php
$schedule->command('garbageman:purge')
         ->daily();
```

You can use whatever schedule that you need to keep the records purged out.  Just review the list at http://laravel.com/docs/master/scheduling#schedule-frequency-options. 

You can also use any of the advanced configuration options of the task scheduler like "Task Output" or "Task Hooks" as listed on the [laravel documentation](http://laravel.com/docs/master/scheduling).

## Configure models to cleanup

During the install process `config/garbageman.php` as copied to the project.  That file is fully documented.
