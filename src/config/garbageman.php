<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Level to log
    |--------------------------------------------------------------------------
    |
    | The level that log messages are generated, which will display information
    | on the console output and in the logs.
    |
    |       0       Emergency: system is unusable
    |       1       Alert: action must be taken immediately
    |       2       Critical: critical conditions
    |       3       Error: error conditions
    |       4       Warning: warning conditions
    |       5       Notice: normal but significant condition
    |       6       Info: informational messages
    |       7       Debug: debug - level messages
    |
    | There is a key for the console & one for the log.  Here is an example...
    |
    |   'logging_level' => [
    |       'console' => 3,
    |       'log'     => 6,
    |   ],
    */
    'logging_level' => [
        'console' => env('GARBAGEMAN_CONSOLE_LOG_LEVEL', 6),
        'log'     => env('GARBAGEMAN_LOG_LEVEL', 6),
    ],

    /*
    |--------------------------------------------------------------------------
    | Models & age to allow the soft deleted record to stay.
    |--------------------------------------------------------------------------
    |
    | The age is in days for each model.  Here is an example...
    |
    |   schedule' => [
    |       App\ModelOne::class => 14,
    |       App\ModelTwo::class => 30,
    |   ],
    |
    | This would purge any ModelOne's, that were deleted over 14 days ago and any
    | ModelTwo's that are were deleted over 30 days ago.
    */
    'schedule'      => [
        //
    ],

];
