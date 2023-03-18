<?php

namespace Spinen\GarbageMan\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Psr\Log\LoggerInterface as Log;

/**
 * Class PurgeCommand
 */
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
     * These values are used as the default in case there are not any configured.
     *
     * @var array
     */
    protected $logging_level = [
        'console' => 6,
        'log' => 6,
    ];

    /**
     * Dispatcher instance.
     *
     * @var Dispatcher
     */
    protected $dispatcher;

    /**
     * Dispatch events when purging?
     *
     * This value is used as the default in case there it is not configured.
     *
     * @var bool
     */
    protected $dispatch_purge_events = false;

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
        'alert' => 1,
        'critical' => 2,
        'debug' => 7,
        'emergency' => 0,
        'error' => 3,
        'info' => 6,
        'notice' => 5,
        'warning' => 4,
    ];

    /**
     * Lock in the time that the command was called to make sure that we use that as the point of reference.
     *
     * @var Carbon
     */
    protected $now;

    /**
     * Create a new command instance.
     */
    public function __construct(Carbon $carbon, Dispatcher $dispatcher, Log $log)
    {
        parent::__construct();

        $this->now = $carbon->now();
        $this->dispatcher = $dispatcher;
        $this->log = $log;
    }

    /**
     * Dispatch the given event for the record being purged.
     */
    protected function dispatchPurgeEvent(string $event, string $model_name, Model $model, ?bool $halt = true): ?bool
    {
        $event = "garbageman.{$event}: ".$model_name;

        $method = $halt ? 'until' : 'dispatch';

        $this->recordMessage(sprintf('Dispatching event [%s] with method [%s]', $event, $method), 'debug');

        return $this->dispatcher->{$method}($event, $model);
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->dispatch_purge_events = $this->laravel->make('config')
                                                     ->get(
                                                         'garbageman.dispatch_purge_events',
                                                         $this->dispatch_purge_events
                                                     );

        $this->logging_level = $this->laravel->make('config')
                                             ->get('garbageman.logging_level', $this->logging_level);

        $schedule = $this->laravel->make('config')
                                  ->get('garbageman.schedule', []);

        foreach ($schedule as $model => $days) {
            $this->purgeExpiredRecordsForModel($model, $days);
        }

        if (count($schedule) < 1) {
            $this->recordMessage('There were no models configured to purge.', 'notice');
        }
    }

    /**
     * Purge the expired records.
     */
    protected function purgeExpiredRecordsForModel(string $model, int $days): int|bool
    {
        if (! class_exists($model)) {
            $this->recordMessage(sprintf('The model [%s] was not found.', $model), 'warning');

            return false;
        }

        if (! method_exists($model, 'forceDelete')) {
            $this->recordMessage(sprintf('The model [%s] does not support soft deleting.', $model), 'error');

            return false;
        }

        $expiration = $this->now->copy()
                                ->subDays($days);

        $query = $this->laravel->make($model)
                               ->where('deleted_at', '<', $expiration)
                               ->onlyTrashed();

        $count = $this->purgeRecordsAsConfigured($query, $model);

        $this->recordMessage(
            sprintf(
                'Purged %s record(s) for %s that was deleted before %s days ago.',
                $count,
                $model,
                $expiration->toIso8601String()
            )
        );

        return $count;
    }

    /**
     * Either purge all the records at once or loop through them one by one.
     *
     * This is to allow events to get dispatched for each record if needed.
     */
    protected function purgeRecordsAsConfigured(Builder $query, string $model_name): int
    {
        if ($this->dispatch_purge_events !== true) {
            $this->recordMessage('Deleting all the records in a single query statement.');

            return $query->forceDelete();
        }

        $this->recordMessage('Deleting each record separately and dispatching events.');

        $records = $query->get();

        foreach ($records as $record) {
            $this->dispatchPurgeEvent('purging', $model_name, $record);

            $record->forceDelete();

            $this->dispatchPurgeEvent('purged', $model_name, $record);
        }

        return $records->count();
    }

    /**
     * Log the action that was taken on the record.
     */
    protected function recordMessage(string $message, ?string $level = null): void
    {
        if (is_null($level)) {
            $level = 'info';
        }

        $console_map = [
            'alert' => 'error',
            'critical' => 'error',
            'debug' => 'line',
            'emergency' => 'error',
            'error' => 'error',
            'info' => 'info',
            'notice' => 'comment',
            'warning' => 'warn',
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
     */
    protected function supposedToLogAtThisLevel(string $level, string $type): bool
    {
        if (! array_key_exists($type, $this->logging_level)) {
            return true;
        }

        return $this->log_levels[$level] <= $this->logging_level[$type];
    }
}
