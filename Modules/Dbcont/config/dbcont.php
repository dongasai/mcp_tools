<?php

return [
    // 默认配置
    'default' => [
        'timeout' => 30,                    // 默认查询超时时间（秒）
        'max_result_rows' => 1000,          // 默认最大结果行数
        'max_result_size' => '10MB',        // 默认最大结果大小
    ],

    // 安全配置
    'security' => [
        'enable_sql_validation' => true,    // 启用SQL验证
        'allowed_operations' => [           // 允许的SQL操作
            'SELECT', 'INSERT', 'UPDATE', 'DELETE'
        ],
        'denied_keywords' => [              // 禁止的SQL关键字
            'DROP', 'TRUNCATE', 'ALTER'
        ],
        'enable_ip_whitelist' => false,     // 启用IP白名单
        'ip_whitelist' => [],               // IP白名单列表
    ],

    // 连接配置
    'connections' => [
        'pool_size' => 10,                  // 连接池大小
        'idle_timeout' => 300,              // 空闲连接超时时间（秒）
        'retry_attempts' => 3,              // 重连尝试次数
        'retry_delay' => 1000,              // 重连延迟时间（毫秒）
    ],

    // 日志配置
    'logging' => [
        'enable_query_log' => true,         // 启用查询日志
        'enable_performance_log' => true,   // 启用性能日志
        'log_slow_queries' => true,         // 记录慢查询
        'slow_query_threshold' => 1000,     // 慢查询阈值（毫秒）
    ],
];