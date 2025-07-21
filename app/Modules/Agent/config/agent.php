<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Agent Configuration
    |--------------------------------------------------------------------------
    |
    | Agent模块的配置选项
    |
    */

    // Agent默认设置
    'defaults' => [
        'status' => 'pending',
        'capabilities' => [],
        'configuration' => [],
    ],

    // Agent状态配置
    'statuses' => [
        'active' => 'Active',
        'inactive' => 'Inactive',
        'suspended' => 'Suspended',
        'pending' => 'Pending',
    ],

    // Agent能力配置
    'capabilities' => [
        'code_generation' => 'Code Generation',
        'code_review' => 'Code Review',
        'testing' => 'Testing',
        'documentation' => 'Documentation',
        'deployment' => 'Deployment',
        'monitoring' => 'Monitoring',
        'debugging' => 'Debugging',
        'refactoring' => 'Refactoring',
        'api_integration' => 'API Integration',
        'database_management' => 'Database Management',
        'security_analysis' => 'Security Analysis',
        'performance_optimization' => 'Performance Optimization',
    ],

    // Agent限制配置
    'limits' => [
        'max_agents_per_user' => env('AGENT_MAX_PER_USER', 10),
        'max_active_agents_per_user' => env('AGENT_MAX_ACTIVE_PER_USER', 5),
        'max_name_length' => 255,
        'max_description_length' => 1000,
    ],

    // Agent ID生成配置
    'id_generation' => [
        'prefix' => env('AGENT_ID_PREFIX', ''),
        'suffix_length' => 8,
        'separator' => '_',
    ],

    // Agent活跃状态配置
    'activity' => [
        'inactive_threshold_hours' => env('AGENT_INACTIVE_THRESHOLD', 24),
        'auto_deactivate_inactive' => env('AGENT_AUTO_DEACTIVATE', false),
    ],

    // Agent通知配置
    'notifications' => [
        'agent_created' => true,
        'agent_activated' => true,
        'agent_deactivated' => true,
        'agent_deleted' => true,
        'status_changed' => true,
    ],

    // Agent权限配置
    'permissions' => [
        'create_agent' => 'user',
        'update_agent' => 'owner',
        'delete_agent' => 'owner',
        'activate_agent' => 'owner',
        'deactivate_agent' => 'owner',
        'view_agent' => 'owner',
        'list_agents' => 'user',
    ],

    // Agent集成配置
    'integrations' => [
        'github' => [
            'enabled' => env('AGENT_GITHUB_INTEGRATION', false),
            'webhook_secret' => env('AGENT_GITHUB_WEBHOOK_SECRET'),
        ],
        'slack' => [
            'enabled' => env('AGENT_SLACK_INTEGRATION', false),
            'webhook_url' => env('AGENT_SLACK_WEBHOOK_URL'),
        ],
        'discord' => [
            'enabled' => env('AGENT_DISCORD_INTEGRATION', false),
            'webhook_url' => env('AGENT_DISCORD_WEBHOOK_URL'),
        ],
    ],

    // Agent监控配置
    'monitoring' => [
        'enabled' => env('AGENT_MONITORING_ENABLED', true),
        'metrics' => [
            'task_completion_rate',
            'response_time',
            'error_rate',
            'uptime',
        ],
        'alerts' => [
            'high_error_rate_threshold' => 0.1,
            'slow_response_threshold_ms' => 5000,
            'low_uptime_threshold' => 0.95,
        ],
    ],
];
