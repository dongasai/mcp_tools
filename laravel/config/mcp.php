<?php

return [
    /*
    |--------------------------------------------------------------------------
    | MCP Server Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for the MCP (Model Context Protocol)
    | server. You can configure the transport, capabilities, and other settings.
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
    | SSE Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Server-Sent Events transport
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
    | Agent Access Control
    |--------------------------------------------------------------------------
    |
    | Configuration for Agent authentication and authorization
    |
    */

    'access_control' => [
        'enabled' => env('MCP_ENABLE_ACCESS_CONTROL', true),
        'default_permissions' => env('MCP_DEFAULT_PERMISSIONS', 'read'),
        'token_expiry' => env('MCP_TOKEN_EXPIRY', 86400), // 24 hours
        'max_agents_per_user' => env('MCP_MAX_AGENTS_PER_USER', 10),
        'require_agent_id' => env('MCP_REQUIRE_AGENT_ID', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Capabilities
    |--------------------------------------------------------------------------
    |
    | Define what capabilities this MCP server provides
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
    | Feature Toggles
    |--------------------------------------------------------------------------
    |
    | Enable or disable specific features
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
    | GitHub Integration
    |--------------------------------------------------------------------------
    |
    | Configuration for GitHub API integration
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
    | Audit and Logging
    |--------------------------------------------------------------------------
    |
    | Configuration for permission auditing and logging
    |
    */

    'audit' => [
        'enabled' => env('ENABLE_PERMISSION_AUDIT', true),
        'retention_days' => env('AUDIT_LOG_RETENTION_DAYS', 90),
        'log_channel' => env('MCP_AUDIT_LOG_CHANNEL', 'daily'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Cache settings for MCP resources and data
    |
    */

    'cache' => [
        'enabled' => env('MCP_CACHE_ENABLED', true),
        'prefix' => env('MCP_CACHE_PREFIX', 'mcp_'),
        'ttl' => env('MCP_CACHE_TTL', 3600), // Default TTL in seconds
        'specific_ttl' => [
            'projects' => env('MCP_CACHE_PROJECTS_TTL', 3600), // 1 hour
            'tasks' => env('MCP_CACHE_TASKS_TTL', 300), // 5 minutes
            'github' => env('MCP_CACHE_GITHUB_TTL', 900), // 15 minutes
            'agents' => env('MCP_CACHE_AGENTS_TTL', 60), // 1 minute
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Settings
    |--------------------------------------------------------------------------
    |
    | Performance and optimization settings
    |
    */

    'performance' => [
        'async_processing' => env('MCP_ASYNC_PROCESSING', true),
        'queue_connection' => env('MCP_QUEUE_CONNECTION', 'database'),
        'memory_limit' => env('MCP_MEMORY_LIMIT', '256M'),
        'gc_interval' => env('MCP_GC_INTERVAL', 300), // 5 minutes
    ],
];
