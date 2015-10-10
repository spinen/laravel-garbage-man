<?php

namespace Spinen\GarbageMan;

use Illuminate\Support\ServiceProvider;

/**
 * Class GarbageManServiceProvider
 *
 * @package Spinen\GarbageMan
 */
class GarbageManServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        $config_file = realpath(__DIR__ . '/config/garbageman.php');

        $this->publishes([
            $config_file => $this->app['path.config'] . DIRECTORY_SEPARATOR . 'garbageman.php',
        ]);

        $this->mergeConfigFrom($config_file, 'garbageman');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
