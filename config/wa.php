<?php

return [

    /*
    |--------------------------------------------------------------------------
    | WhatsApp Driver
    |--------------------------------------------------------------------------
    | "gateway" sends messages through the wa-gateway service.
    | "log" keeps everything in the database / log file and never talks to
    | WhatsApp, so the whole flow can be exercised locally and in tests.
    */

    'driver' => env('WA_DRIVER', 'log'),

    'gateway' => [
        'url' => env('GATEWAY_URL', 'http://127.0.0.1:3001'),
        'token' => env('GATEWAY_TOKEN', ''),
        'timeout' => (int) env('GATEWAY_TIMEOUT', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | Local gateway process control
    |--------------------------------------------------------------------------
    | Only enabled for local Windows development. In production the gateway
    | must be managed by systemd, PM2, or another process supervisor.
    */

    'process' => [
        'enabled' => (bool) env('WA_GATEWAY_PROCESS_ENABLED', false),
        'path' => env('WA_GATEWAY_PATH'),
        'node' => env('WA_GATEWAY_NODE', 'node'),
        'script' => env('WA_GATEWAY_SCRIPT', 'src/index.js'),
        'pid_file' => storage_path('app/wa-gateway.pid'),
        'out_log' => storage_path('logs/wa-gateway.out.log'),
        'err_log' => storage_path('logs/wa-gateway.err.log'),
        'start_timeout' => (int) env('WA_GATEWAY_START_TIMEOUT', 10),
    ],

    'queue_process' => [
        'enabled' => (bool) env('WA_QUEUE_PROCESS_ENABLED', false),
        'php' => env('WA_QUEUE_PHP', PHP_BINARY),
        'tries' => (int) env('WA_QUEUE_TRIES', 3),
        'start_timeout' => (int) env('WA_QUEUE_START_TIMEOUT', 10),
        'heartbeat_stale_seconds' => (int) env('WA_QUEUE_HEARTBEAT_STALE_SECONDS', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Chatbot behaviour
    |--------------------------------------------------------------------------
    | debounce_min_seconds / debounce_max_seconds: random wait before the AI
    |   replies, so bursts of messages from the same session are merged into a
    |   single AI turn and the pacing feels less robotic.
    | history_limit: maximum number of previous messages sent to Gemini.
    | max_attempts: fallback when the "max_attempts" setting is missing.
    */

    'debounce_min_seconds' => (int) env('WA_DEBOUNCE_MIN_SECONDS', 3),

    'debounce_max_seconds' => (int) env('WA_DEBOUNCE_MAX_SECONDS', 6),

    'history_limit' => (int) env('WA_HISTORY_LIMIT', 20),

    'max_attempts' => (int) env('WA_MAX_ATTEMPTS', 3),

    'outgoing' => [
        'tries' => (int) env('WA_OUTGOING_TRIES', 3),
        'backoff' => [15, 45, 120],
    ],

];
