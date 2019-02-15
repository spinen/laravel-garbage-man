<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Dispatch events on purge of each record.
    |--------------------------------------------------------------------------
    |
    | Allow hook into the purge of each record by throwing events before & after
    | deleting of each record. There are 2 events thrown:
    |
    |       1) garbageman.purging:<full/model/name>
    |       2) garbageman.purged:<full/model/name>
    |
    | The model is passed with each of the events. The "purging" event is thrown
    | just before the actual delete & the purged is thrown just after the actual
    | delete.
    |
    | This is an expensive operation as it requires a SQL command for each record
    | to delete so that the record can be thrown with the events. Therefore,
    | unless you need to catch the events to preform some other action, leave
    | this false to allow all records per model to get deleted with a single
    | SQL call.
    */
    'dispatch_purge_events' => false,

    /*
    |--------------------------------------------------------------------------
    | Level to log.
    |--------------------------------------------------------------------------
    |
    | The level that log messages are generated, which will display information
    | on the console output and in the logs.
    |
    |       0           Emergency: system is unusable
    |       1           Alert: action must be taken immediately
    |       2           Critical: critical conditions
    |       3           Error: error conditions
    |       4           Warning: warning conditions
    |       5           Notice: normal but significant condition
    |       6 (default) Info: informational messages
    |       7           Debug: debug - level messages
    |
    | There is a key for the console & one for the log. Here is an example...
    |
    |   'logging_level' => [
    |       'console' => 3,
    |       'log'     => 6,
    |   ],
    */
    'logging_level'         => [
        'console' => env('GARBAGEMAN_CONSOLE_LOG_LEVEL', 6),
        'log'     => env('GARBAGEMAN_LOG_LEVEL', 6),
    ],

    /*
    |--------------------------------------------------------------------------
    | Models & age to allow the soft deleted record to stay.
    |--------------------------------------------------------------------------
    |
    | The age is in days for each model. Here is an example...
    |
    |   'schedule' => [
    |       App\ModelOne::class => 14,
    |       App\ModelTwo::class => 30,
    |   ],
    |
    | This would purge any ModelOnes that were deleted over 14 days ago and any
    | ModelTwos that are were deleted over 30 days ago.
    */
    'schedule'              => [
        //
    ],

];
