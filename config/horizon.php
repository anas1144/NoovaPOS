<?php

use Illuminate\Support\Str;

return [

    /*
    |--------------------------------------------------------------------------
    | Horizon Domain
    |--------------------------------------------------------------------------
    */
    'domain' => env('HORIZON_DOMAIN'),

    /*
    |--------------------------------------------------------------------------
    | Horizon Path
    |--------------------------------------------------------------------------
    */
    'path' => env('HORIZON_PATH', 'horizon'),

    /*
    |--------------------------------------------------------------------------
    | Horizon Redis Connection
    |--------------------------------------------------------------------------
    */
    'use' => 'default',

    'prefix' => env(
        'HORIZON_PREFIX',
        Str::slug(env('APP_NAME', 'noovapos'), '_') . '_horizon:'
    ),

    'middleware' => ['web', 'auth:sanctum'],

    /*
    |--------------------------------------------------------------------------
    | Queue Wait Time Thresholds
    |--------------------------------------------------------------------------
    */
    'waits' => [
        'redis:default' => 60,
        'redis:fbr' => 60,
        'redis:notifications' => 30,
        'redis:imports' => 120,
    ],

    /*
    |--------------------------------------------------------------------------
    | Job Trimming Times
    |--------------------------------------------------------------------------
    */
    'trim' => [
        'recent' => 60,
        'pending' => 60,
        'completed' => 60,
        'recent_failed' => 10080,
        'failed' => 10080,
        'monitored' => 10080,
    ],

    /*
    |--------------------------------------------------------------------------
    | Memory Limit (MB)
    |--------------------------------------------------------------------------
    */
    'memory_limit' => 256,

    /*
    |--------------------------------------------------------------------------
    | Queue Worker Configuration
    |--------------------------------------------------------------------------
    */
    'defaults' => [
        'supervisor-default' => [
            'connection' => 'redis',
            'queue' => ['default'],
            'balance' => 'auto',
            'autoScalingStrategy' => 'time',
            'minProcesses' => 1,
            'maxProcesses' => 6,
            'balanceMaxShift' => 1,
            'balanceCooldown' => 3,
            'maxTime' => 0,
            'maxJobs' => 0,
            'memory' => 256,
            'tries' => 3,
            'timeout' => 60,
            'nice' => 0,
        ],
        'supervisor-fbr' => [
            'connection' => 'redis',
            'queue' => ['fbr'],
            'balance' => 'simple',
            'minProcesses' => 1,
            'maxProcesses' => 4,
            'memory' => 256,
            'tries' => 5,
            'timeout' => 120,
        ],
        'supervisor-notifications' => [
            'connection' => 'redis',
            'queue' => ['notifications'],
            'balance' => 'simple',
            'minProcesses' => 1,
            'maxProcesses' => 4,
            'memory' => 256,
            'tries' => 3,
            'timeout' => 60,
        ],
        'supervisor-imports' => [
            'connection' => 'redis',
            'queue' => ['imports'],
            'balance' => 'simple',
            'minProcesses' => 1,
            'maxProcesses' => 2,
            'memory' => 512,
            'tries' => 1,
            'timeout' => 600,
        ],
    ],

    'environments' => [
        'production' => [
            'supervisor-default' => ['minProcesses' => 2, 'maxProcesses' => 10],
            'supervisor-fbr' => ['minProcesses' => 2, 'maxProcesses' => 6],
            'supervisor-notifications' => ['minProcesses' => 2, 'maxProcesses' => 6],
            'supervisor-imports' => ['minProcesses' => 1, 'maxProcesses' => 3],
        ],
        'local' => [
            'supervisor-default' => ['minProcesses' => 1, 'maxProcesses' => 2],
            'supervisor-fbr' => ['minProcesses' => 1, 'maxProcesses' => 1],
            'supervisor-notifications' => ['minProcesses' => 1, 'maxProcesses' => 1],
            'supervisor-imports' => ['minProcesses' => 1, 'maxProcesses' => 1],
        ],
    ],
];
