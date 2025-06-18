<?php

return [
    /*
    |--------------------------------------------------------------------------
    | MCP 服务器配置
    |--------------------------------------------------------------------------
    |
    | 此文件包含 MCP (Model Context Protocol) 服务器的配置。
    | 您可以配置传输方式、功能和其他设置。
    |
    */

    'server' => [
        'name' => env('MCP_SERVER_NAME', 'MCP Tools Server'),
        'version' => env('MCP_SERVER_VERSION', '1.0.0'),
        'protocol_version' => env('MCP_PROTOCOL_VERSION', '1.0'),
        'transport' => env('MCP_TRANSPORT', 'sse'), // stdio, sse, http
    ],

    /*
    |--------------------------------------------------------------------------
    | SSE 配置
    |--------------------------------------------------------------------------
    |
    | Server-Sent Events 传输的配置
    |
    */

    'sse' => [
        'endpoint' => env('SSE_ENDPOINT', '/mcp/sse/connect'),
        'host' => env('MCP_SERVER_HOST', 'localhost'),
        'port' => env('MCP_SERVER_PORT', 8000),
        'heartbeat_interval' => env('SSE_HEARTBEAT_INTERVAL', 30),
        'connection_timeout' => env('SSE_CONNECTION_TIMEOUT', 300),
        'max_connections' => env('MCP_MAX_CONNECTIONS', 1000),
        'cors' => [
            'enabled' => env('MCP_CORS_ENABLED', true),
            'origins' => env('MCP_CORS_ORIGINS', '*'),
            'headers' => env('MCP_CORS_HEADERS', 'Authorization, Agent-ID, Content-Type'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Agent 访问控制
    |--------------------------------------------------------------------------
    |
    | Agent 认证和授权的配置
    |
    */

    'access_control' => [
        'enabled' => env('MCP_ENABLE_ACCESS_CONTROL', true),
        'default_permissions' => env('MCP_DEFAULT_PERMISSIONS', 'read'),
        'token_expiry' => env('MCP_TOKEN_EXPIRY', 86400), // 24小时
        'max_agents_per_user' => env('MCP_MAX_AGENTS_PER_USER', 10),
        'require_agent_id' => env('MCP_REQUIRE_AGENT_ID', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | 功能配置
    |--------------------------------------------------------------------------
    |
    | 定义此 MCP 服务器提供的功能
    |
    */

    'capabilities' => [
        'resources' => env('MCP_ENABLE_RESOURCES', true),
        'tools' => env('MCP_ENABLE_TOOLS', true),
        'prompts' => env('MCP_ENABLE_PROMPTS', true),
        'notifications' => env('MCP_ENABLE_NOTIFICATIONS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | 功能开关
    |--------------------------------------------------------------------------
    |
    | 启用或禁用特定功能
    |
    */

    'features' => [
        'projects' => env('MCP_ENABLE_PROJECTS', true),
        'tasks' => env('MCP_ENABLE_TASKS', true),
        'github' => env('MCP_ENABLE_GITHUB', true),
        'agents' => env('MCP_ENABLE_AGENTS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | GitHub 集成
    |--------------------------------------------------------------------------
    |
    | GitHub API 集成的配置
    |
    */

    'github' => [
        'token' => env('GITHUB_TOKEN'),
        'webhook_secret' => env('GITHUB_WEBHOOK_SECRET'),
        'api_url' => env('GITHUB_API_URL', 'https://api.github.com'),
        'rate_limit' => env('GITHUB_RATE_LIMIT', 5000),
    ],

    /*
    |--------------------------------------------------------------------------
    | 审计和日志
    |--------------------------------------------------------------------------
    |
    | 权限审计和日志记录的配置
    |
    */

    'audit' => [
        'enabled' => env('ENABLE_PERMISSION_AUDIT', true),
        'retention_days' => env('AUDIT_LOG_RETENTION_DAYS', 90),
        'log_channel' => env('MCP_AUDIT_LOG_CHANNEL', 'daily'),
    ],

    /*
    |--------------------------------------------------------------------------
    | 缓存配置
    |--------------------------------------------------------------------------
    |
    | MCP 资源和数据的缓存设置
    |
    */

    'cache' => [
        'enabled' => env('MCP_CACHE_ENABLED', true),
        'prefix' => env('MCP_CACHE_PREFIX', 'mcp_'),
        'ttl' => env('MCP_CACHE_TTL', 3600), // 默认TTL（秒）
        'specific_ttl' => [
            'projects' => env('MCP_CACHE_PROJECTS_TTL', 3600), // 1小时
            'tasks' => env('MCP_CACHE_TASKS_TTL', 300), // 5分钟
            'github' => env('MCP_CACHE_GITHUB_TTL', 900), // 15分钟
            'agents' => env('MCP_CACHE_AGENTS_TTL', 60), // 1分钟
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 性能设置
    |--------------------------------------------------------------------------
    |
    | 性能和优化设置
    |
    */

    'performance' => [
        'async_processing' => env('MCP_ASYNC_PROCESSING', true),
        'queue_connection' => env('MCP_QUEUE_CONNECTION', 'database'),
        'memory_limit' => env('MCP_MEMORY_LIMIT', '256M'),
        'gc_interval' => env('MCP_GC_INTERVAL', 300), // 5分钟
    ],
];
