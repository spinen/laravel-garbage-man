<?php

namespace Spinen\GarbageMan;

use Illuminate\Support\ServiceProvider;
use Spinen\GarbageMan\Commands\PurgeCommand;

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
        $this->publishes(
            [
                realpath(__DIR__ . '/config/garbageman.php') => config_path('garbageman.php'),
            ],
            'config'
        );
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(
            'command.garbageman.purge',
            function ($app) {
                return $app->make(PurgeCommand::class);
            }
        );

        $this->commands('command.garbageman.purge');
    }
}
