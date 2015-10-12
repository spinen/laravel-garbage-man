<?php

namespace Spinen\GarbageMan\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Log\Writer as Log;

class PurgeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'garbageman:purge';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Purge the soft deleted records based on the age in the configuration file.';

    /**
     * The configured level to log information.
     *
     * @var array
     */
    protected $configured_logging_level = [
        'console' => 6,
        'log'     => 6,
    ];

    /**
     * Logging instance.
     *
     * @var Log
     */
    protected $log;

    /**
     * Log levels to know the hierarchy.
     *
     * @var array
     */
    protected $log_levels = [
        'alert'     => 1,
        'critical'  => 2,
        'debug'     => 7,
        'emergency' => 0,
        'error'     => 3,
        'info'      => 6,
        'notice'    => 5,
        'warning'   => 4,
    ];

    /**
     * Lock in the time that the command was called to make sure that we use that as the point of reference.
     *
     * @var Carbon
     */
    protected $now;

    /**
     * Create a new command instance.
     *
     * @param Carbon $carbon
     * @param Log    $log
     */
    public function __construct(Carbon $carbon, Log $log)
    {
        parent::__construct();

        $this->now = $carbon->now();

        $this->log = $log;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->configured_logging_level = $this->laravel->make('config')
                                                        ->get('garbageman.logging_level',
                                                            $this->configured_logging_level);

        $schedule = $this->laravel->make('config')
                                  ->get('garbageman.schedule', []);

        foreach ($schedule as $model => $days) {
            $this->purgeExpiredRecordsForModel($model, $days);
        }

        if (count($schedule) < 1) {
            $this->recordMessage("There were no models configured to purge.", 'notice');
        }
    }

    /**
     * Purge the expired records.
     *
     * @param string $model
     * @param int    $days
     *
     * @return int|boolean
     */
    protected function purgeExpiredRecordsForModel($model, $days)
    {
        if (!class_exists($model)) {
            $this->recordMessage(sprintf("The model [%s] was not found.", $model), 'warning');

            return false;
        }

        if (!method_exists($model, 'onlyTrashed') || !method_exists($model, 'forceDelete')) {
            $this->recordMessage(sprintf("The model [%s] does not support soft deleting.", $model), 'error');

            return false;
        }

        $expiration = $this->now->copy()
                                ->subDays($days);

        $count = $this->laravel->make($model)
                               ->where('deleted_at', '<', $expiration)
                               ->onlyTrashed()
                               ->forceDelete();

        $this->recordMessage(sprintf("Purged %s record(s) for %s that was deleted before %s.", $count, $model,
            $expiration->toIso8601String()));

        return $count;
    }

    protected function recordMessage($message, $level = null)
    {
        if (is_null($level)) {
            $level = 'info';
        }

        $console_map = [
            'alert'     => 'error',
            'critical'  => 'error',
            'debug'     => 'line',
            'emergency' => 'error',
            'error'     => 'error',
            'info'      => 'info',
            'notice'    => 'comment',
            'warning'   => 'warn',
        ];

        if ($this->supposedToLogAtThisLevel($level, 'log')) {
            $this->log->{$level}($message);
        }

        if ($this->supposedToLogAtThisLevel($level, 'console')) {
            $this->{$console_map[$level]}($message);
        }
    }

    /**
     * Decide if the system is supposed to log for the level.
     *
     * Default to true, if not configured.
     *
     * @param string $level
     * @param string $type
     *
     * @return bool
     */
    protected function supposedToLogAtThisLevel($level, $type)
    {
        if (!array_key_exists($type, $this->configured_logging_level)) {
            return true;
        }

        return $this->log_levels[$level] <= $this->configured_logging_level[$type];
    }
}
