<?php

return [
    /*
    |--------------------------------------------------------------------------
    | 缓存配置
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'default_ttl' => env('CORE_CACHE_TTL', 3600),
        'prefix' => env('CORE_CACHE_PREFIX', 'mcp_core_'),
        'tags_enabled' => env('CORE_CACHE_TAGS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | 日志配置
    |--------------------------------------------------------------------------
    */
    'logging' => [
        'channels' => [
            'performance' => env('CORE_LOG_PERFORMANCE_CHANNEL', 'daily'),
            'audit' => env('CORE_LOG_AUDIT_CHANNEL', 'database'),
            'error' => env('CORE_LOG_ERROR_CHANNEL', 'stack'),
        ],
        'level' => env('CORE_LOG_LEVEL', 'info'),
        'max_files' => env('CORE_LOG_MAX_FILES', 14),
    ],

    /*
    |--------------------------------------------------------------------------
    | 事件配置
    |--------------------------------------------------------------------------
    */
    'events' => [
        'async_enabled' => env('CORE_EVENTS_ASYNC', true),
        'retry_attempts' => env('CORE_EVENTS_RETRY', 3),
        'retry_delay' => env('CORE_EVENTS_DELAY', 5),
        'queue_connection' => env('CORE_EVENTS_QUEUE', 'database'),
    ],

    /*
    |--------------------------------------------------------------------------
    | 验证配置
    |--------------------------------------------------------------------------
    */
    'validation' => [
        'strict_mode' => env('CORE_VALIDATION_STRICT', true),
        'cache_rules' => env('CORE_VALIDATION_CACHE', true),
        'custom_messages' => [
            'zh-CN' => [
                'required' => ':attribute 不能为空',
                'email' => ':attribute 必须是有效的邮箱地址',
                'min' => ':attribute 最少需要 :min 个字符',
                'max' => ':attribute 最多只能 :max 个字符',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 性能配置
    |--------------------------------------------------------------------------
    */
    'performance' => [
        'memory_limit' => env('CORE_MEMORY_LIMIT', '256M'),
        'execution_timeout' => env('CORE_EXECUTION_TIMEOUT', 300),
        'max_concurrent_requests' => env('CORE_MAX_CONCURRENT', 100),
        'enable_profiling' => env('CORE_ENABLE_PROFILING', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | 安全配置
    |--------------------------------------------------------------------------
    */
    'security' => [
        'rate_limiting' => [
            'enabled' => env('CORE_RATE_LIMITING', true),
            'max_attempts' => env('CORE_RATE_MAX_ATTEMPTS', 60),
            'decay_minutes' => env('CORE_RATE_DECAY_MINUTES', 1),
        ],
        'csrf_protection' => env('CORE_CSRF_PROTECTION', true),
        'xss_protection' => env('CORE_XSS_PROTECTION', true),
    ],
];
