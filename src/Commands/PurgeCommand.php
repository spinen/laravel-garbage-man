<?php

namespace Spinen\GarbageMan\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;

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
     * Lock in the time that the command was called to make sure that we use that as the point of reference.
     *
     * @var Carbon
     */
    protected $now;

    /**
     * Create a new command instance.
     *
     * @param Carbon $carbon
     */
    public function __construct(Carbon $carbon)
    {
        parent::__construct();

        $this->now = $carbon->now();
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $schedule = $this->laravel->make('config')
                                  ->get('garbageman.schedule', []);

        foreach ($schedule as $model => $days) {
            $this->purgeExpiredRecordsForModel($model, $days);
        }

        if (count($schedule) < 1) {
            $this->comment("There were no models configured to purge.");
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
            $this->warn(sprintf("The model [%s] was not found.", $model));

            return false;
        }

        if (!method_exists($model, 'onlyTrashed') || !method_exists($model, 'forceDelete')) {
            $this->error(sprintf("The model [%s] does not support soft deleting.", $model));

            return false;
        }

        $expiration = $this->now->copy()
                                ->subDays($days);

        $count = $this->laravel->make($model)
                               ->where('deleted_at', '<', $expiration)
                               ->onlyTrashed()
                               ->forceDelete();

        $this->info(sprintf("Purged %s record(s) for %s that was deleted before %s.", $count, $model,
            $expiration->toIso8601String()));

        return $count;
    }
}
