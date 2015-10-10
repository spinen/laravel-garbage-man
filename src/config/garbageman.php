<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Filter type
    |--------------------------------------------------------------------------
    |
    | The filter is to apply blocking ("block") or allowing ("allow") strategy
    | using the rules defined below.
    |
    */
    'type'    => 'block',

    /*
    |--------------------------------------------------------------------------
    | Blocked devices, browsers and/or versions
    |--------------------------------------------------------------------------
    |
    | This array defines the items to be filtered out when a request is made
    | to all routes.  There is a three level structure to the array where it
    | goes device -> browser -> version.  The device string can be "Mobile",
    | "Other", or "Tablet" as defined by UAParser\Parser. You can define an
    | array of specific browser names that you are targeting or use "*" to
    | block all browsers of that device type.  At the final level, you can
    | define an array of comparison operators to use for that specific browser
    | or you can use a "*" to block all versions of that browser.  We are
    | using php's version_compare function...
    |
    |      @link http://php.net/manual/en/function.version-compare.php
    |
    | so you can see the operations documented there.
    |
    | Here is an example...
    |
    | 'rules' => [
    |     'Mobile' => '*',
    |     'Other'  => [
    |         'IE'    => '*',
    |     ],
    |     'Tablet' => [
    |         'Opera' => [
    |             '<' => '6',
    |         ]
    |     ],
    | ],
    |
    | In this example, we are allowing/blocking the following...
    |
    |     * All mobile devices
    |     * All versions of IE that is not on a tablet
    |     * Any version of Opera less than 6 on a tablet
    |
    */
    'rules'   => [
        'Mobile' => [],
        'Other'  => [],
        'Tablet' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Blocked devices, browsers and/or versions
    |--------------------------------------------------------------------------
    |
    | The name of the route to redirect the user to if the browser is blocked
    |
    */
    'route'   => 'incompatible_browser',

    /*
    |--------------------------------------------------------------------------
    | Duration to cache the browser as being blocked
    |--------------------------------------------------------------------------
    |
    | The time in minutes to cache that the client is blocked
    |
    */
    'timeout' => 3600,

];
