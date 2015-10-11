<?php

return [

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
    'schedule' => [
        //
    ],

];
