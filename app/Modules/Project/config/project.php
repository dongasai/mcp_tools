<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Project Configuration
    |--------------------------------------------------------------------------
    |
    | Project模块的配置选项
    |
    */

    // 项目默认设置
    'defaults' => [
        'status' => 'active',
        'priority' => 'medium',
        'branch' => 'main',
        'settings' => [],
    ],

    // 项目状态配置
    'statuses' => [
        'active' => 'Active',
        'inactive' => 'Inactive',
        'completed' => 'Completed',
        'archived' => 'Archived',
        'suspended' => 'Suspended',
    ],

    // 项目优先级配置
    'priorities' => [
        'low' => 'Low',
        'medium' => 'Medium',
        'high' => 'High',
        'urgent' => 'Urgent',
    ],

    // 项目限制配置
    'limits' => [
        'max_projects_per_user' => env('PROJECT_MAX_PER_USER', 50),
        'max_active_projects_per_user' => env('PROJECT_MAX_ACTIVE_PER_USER', 10),
        'max_name_length' => 255,
        'max_description_length' => 1000,
    ],

    // 项目设置配置
    'settings' => [
        'auto_assign_tasks' => false,
        'enable_notifications' => true,
        'require_approval' => false,
        'allow_public_access' => false,
        'enable_time_tracking' => true,
        'enable_file_uploads' => true,
        'max_file_size_mb' => 10,
        'allowed_file_types' => ['pdf', 'doc', 'docx', 'txt', 'md', 'zip'],
    ],

    // 项目权限配置
    'permissions' => [
        'create_project' => 'user',
        'update_project' => 'owner',
        'delete_project' => 'owner',
        'view_project' => 'owner',
        'list_projects' => 'user',
        'assign_agent' => 'owner',
        'change_status' => 'owner',
    ],

    // 项目集成配置
    'integrations' => [
        'github' => [
            'enabled' => env('PROJECT_GITHUB_INTEGRATION', false),
            'auto_sync' => env('PROJECT_GITHUB_AUTO_SYNC', false),
            'webhook_secret' => env('PROJECT_GITHUB_WEBHOOK_SECRET'),
        ],
        'gitlab' => [
            'enabled' => env('PROJECT_GITLAB_INTEGRATION', false),
            'auto_sync' => env('PROJECT_GITLAB_AUTO_SYNC', false),
        ],
        'bitbucket' => [
            'enabled' => env('PROJECT_BITBUCKET_INTEGRATION', false),
            'auto_sync' => env('PROJECT_BITBUCKET_AUTO_SYNC', false),
        ],
        'jira' => [
            'enabled' => env('PROJECT_JIRA_INTEGRATION', false),
            'auto_create_issues' => env('PROJECT_JIRA_AUTO_CREATE', false),
        ],
        'trello' => [
            'enabled' => env('PROJECT_TRELLO_INTEGRATION', false),
            'auto_create_cards' => env('PROJECT_TRELLO_AUTO_CREATE', false),
        ],
    ],

    // 项目通知配置
    'notifications' => [
        'project_created' => true,
        'project_updated' => true,
        'project_completed' => true,
        'project_deleted' => true,
        'status_changed' => true,
        'agent_assigned' => true,
        'agent_changed' => true,
    ],

    // 项目监控配置
    'monitoring' => [
        'enabled' => env('PROJECT_MONITORING_ENABLED', true),
        'track_completion_rate' => true,
        'track_task_progress' => true,
        'track_agent_performance' => true,
        'generate_reports' => true,
        'report_frequency' => 'weekly', // daily, weekly, monthly
    ],

    // 项目模板配置
    'templates' => [
        'web_development' => [
            'name' => 'Web Development',
            'description' => 'Standard web development project template',
            'default_tasks' => [
                'Setup development environment',
                'Create project structure',
                'Implement core features',
                'Write tests',
                'Deploy to production',
            ],
            'default_settings' => [
                'enable_time_tracking' => true,
                'require_approval' => true,
            ],
        ],
        'mobile_app' => [
            'name' => 'Mobile App',
            'description' => 'Mobile application development template',
            'default_tasks' => [
                'Design UI/UX',
                'Setup development environment',
                'Implement core features',
                'Test on devices',
                'Submit to app store',
            ],
        ],
        'api_development' => [
            'name' => 'API Development',
            'description' => 'REST API development template',
            'default_tasks' => [
                'Design API endpoints',
                'Setup database schema',
                'Implement endpoints',
                'Write documentation',
                'Deploy and test',
            ],
        ],
    ],
];
