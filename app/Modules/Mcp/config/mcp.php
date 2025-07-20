<?php

return [
    /*
    |--------------------------------------------------------------------------
    | MCP Server Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the Model Context Protocol server
    |
    */

    'server' => [
        'name' => env('MCP_SERVER_NAME', 'MCP Tools Server'),
        'version' => env('MCP_SERVER_VERSION', '1.0.0'),
        'transport' => env('MCP_TRANSPORT', 'http'),
        'host' => env('MCP_HOST', '0.0.0.0'),
        'port' => env('MCP_PORT', 3001),
    ],

    /*
    |--------------------------------------------------------------------------
    | MCP Capabilities
    |--------------------------------------------------------------------------
    |
    | Define what capabilities this MCP server supports
    |
    */

    'capabilities' => [
        'resources' => true,
        'tools' => true,
        'prompts' => false,
        'logging' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | MCP Resources
    |--------------------------------------------------------------------------
    |
    | Register available MCP resources
    |
    */

    'resources' => [
        'project' => [
            'class' => \App\Modules\Mcp\Resources\ProjectResource::class,
            'description' => 'Access to project information and management',
            'uri_template' => 'project://{path}',
        ],
        'task' => [
            'class' => \App\Modules\Mcp\Resources\TaskResource::class,
            'description' => 'Access to task information and management',
            'uri_template' => 'task://{path}',
        ],
        'agent' => [
            'class' => \App\Modules\Mcp\Resources\AgentResource::class,
            'description' => 'Access to agent information and management',
            'uri_template' => 'agent://{path}',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | MCP Tools
    |--------------------------------------------------------------------------
    |
    | Register available MCP tools
    |
    */

    'tools' => [
        'project_manager' => [
            'class' => \App\Modules\Mcp\Tools\ProjectTool::class,
            'description' => 'Manage projects - create, update, and query project information',
        ],
        'task_manager' => [
            'class' => \App\Modules\Mcp\Tools\TaskTool::class,
            'description' => 'Manage tasks - create, update, and query task information',
        ],
        'agent_manager' => [
            'class' => \App\Modules\Mcp\Tools\AgentTool::class,
            'description' => 'Manage agents - create, update, and query agent information',
        ],
        'ask_question' => [
            'class' => \App\Modules\Mcp\Tools\AskQuestionTool::class,
            'description' => 'Agent向用户提出问题，获取指导、确认或澄清',
        ],
        'get_questions' => [
            'class' => \App\Modules\Mcp\Tools\GetQuestionsTool::class,
            'description' => '获取问题列表，支持多种过滤条件',
        ],
        'check_answer' => [
            'class' => \App\Modules\Mcp\Tools\CheckAnswerTool::class,
            'description' => '检查问题是否已被回答，获取问题的当前状态和回答内容',
        ],
        'question_batch' => [
            'class' => \App\Modules\Mcp\Tools\QuestionBatchTool::class,
            'description' => 'Agent问题批量操作工具 - 批量处理、搜索和分析功能',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication & Security
    |--------------------------------------------------------------------------
    |
    | Configuration for agent authentication and access control
    |
    */

    'auth' => [
        'enabled' => env('MCP_AUTH_ENABLED', true),
        'token_header' => env('MCP_TOKEN_HEADER', 'X-Agent-Token'),
        'agent_header' => env('MCP_AGENT_HEADER', 'X-Agent-ID'),
        'session_timeout' => env('MCP_SESSION_TIMEOUT', 3600), // seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for MCP session and activity logging
    |
    */

    'logging' => [
        'enabled' => env('MCP_LOGGING_ENABLED', true),
        'channel' => env('MCP_LOG_CHANNEL', 'mcp'),
        'level' => env('MCP_LOG_LEVEL', 'info'),
        'log_requests' => env('MCP_LOG_REQUESTS', true),
        'log_responses' => env('MCP_LOG_RESPONSES', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Configuration for API rate limiting
    |
    */

    'rate_limiting' => [
        'enabled' => env('MCP_RATE_LIMITING_ENABLED', true),
        'requests_per_minute' => env('MCP_REQUESTS_PER_MINUTE', 60),
        'burst_limit' => env('MCP_BURST_LIMIT', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | CORS Configuration
    |--------------------------------------------------------------------------
    |
    | Cross-Origin Resource Sharing configuration for MCP endpoints
    |
    */

    'cors' => [
        'enabled' => env('MCP_CORS_ENABLED', true),
        'allowed_origins' => explode(',', env('MCP_CORS_ORIGINS', '*')),
        'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
        'allowed_headers' => ['Content-Type', 'Authorization', 'X-Agent-ID', 'X-Agent-Token'],
    ],
];
