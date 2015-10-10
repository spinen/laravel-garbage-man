# SPINEN's Laravel Garbage Man

The softdeletes are great in laravel to make sure that some deleted data can be recovered.  This package, allows you to
configure an array of models with how many days that you want the softdeleted data to stay in the database.

## Build Status

| Branch | Status |
| ------ | :----: |
| Develop | [![Build Status](https://ci.spinen.net/buildStatus/icon?job=laravel-garbage-man-develop)](https://ci.spinen.net/view/Libraries/job/laravel-garbage-man-develop/) |
| Feature | [![Build Status](https://ci.spinen.net/buildStatus/icon?job=laravel-garbage-man-feature__)](https://ci.spinen.net/view/Libraries/job/laravel-garbage-man-feature__/) |
| Master | [![Build Status](https://ci.spinen.net/buildStatus/icon?job=laravel-garbage-man-master)](https://ci.spinen.net/view/Libraries/job/laravel-garbage-man-master/) |
| Release | [![Build Status](https://ci.spinen.net/buildStatus/icon?job=laravel-garbage-man-release__)](https://ci.spinen.net/view/Libraries/job/laravel-garbage-man-release__/) |

## Prerequisite

As side from Laravel 5.x, there are X packages that are required

* 

## Install

Install Garbage Man:

```bash
$ composer require spinen/laravel-garbage-man
```

Add the Service Provider to `config/app.php`:

```php
'providers' => [
    // ...
    Spinen\BrowserFilter\GarbageManServiceProvider::class,
];
```

Publish the package config file to `config/garbageman.php`:

```bash
$ php artisan vendor:publish
```

## Configure cleanup options

During the install process `config/garbageman.php` as copied to the project.  That file is fully documented.
