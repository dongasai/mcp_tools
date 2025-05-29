<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Task Configuration
    |--------------------------------------------------------------------------
    |
    | Task模块的配置选项
    |
    */

    // 任务默认设置
    'defaults' => [
        'status' => 'pending',
        'type' => 'main',
        'priority' => 'medium',
        'progress' => 0,
    ],

    // 任务状态配置
    'statuses' => [
        'pending' => 'Pending',
        'in_progress' => 'In Progress',
        'completed' => 'Completed',
        'blocked' => 'Blocked',
        'cancelled' => 'Cancelled',
        'on_hold' => 'On Hold',
    ],

    // 任务类型配置
    'types' => [
        'main' => 'Main Task',
        'sub' => 'Sub Task',
        'milestone' => 'Milestone',
        'bug' => 'Bug Fix',
        'feature' => 'Feature',
        'improvement' => 'Improvement',
    ],

    // 任务优先级配置
    'priorities' => [
        'low' => 'Low',
        'medium' => 'Medium',
        'high' => 'High',
        'urgent' => 'Urgent',
    ],

    // 任务限制配置
    'limits' => [
        'max_tasks_per_user' => env('TASK_MAX_PER_USER', 1000),
        'max_active_tasks_per_user' => env('TASK_MAX_ACTIVE_PER_USER', 50),
        'max_sub_tasks_per_main_task' => env('TASK_MAX_SUB_TASKS', 20),
        'max_title_length' => 255,
        'max_description_length' => 2000,
        'max_nesting_level' => 3,
    ],

    // 任务自动化配置
    'automation' => [
        'auto_complete_parent_task' => true,
        'auto_start_sub_tasks' => false,
        'auto_assign_to_agent' => false,
        'auto_update_progress' => true,
        'auto_set_due_dates' => false,
    ],

    // 任务通知配置
    'notifications' => [
        'task_created' => true,
        'task_assigned' => true,
        'task_started' => true,
        'task_completed' => true,
        'task_overdue' => true,
        'task_blocked' => true,
        'status_changed' => true,
        'progress_updated' => false,
        'agent_changed' => true,
    ],

    // 任务权限配置
    'permissions' => [
        'create_task' => 'user',
        'update_task' => 'owner_or_assigned',
        'delete_task' => 'owner',
        'view_task' => 'owner_or_assigned',
        'list_tasks' => 'user',
        'assign_agent' => 'owner',
        'change_status' => 'owner_or_assigned',
        'create_sub_task' => 'owner_or_assigned',
    ],

    // 任务时间跟踪配置
    'time_tracking' => [
        'enabled' => env('TASK_TIME_TRACKING_ENABLED', true),
        'auto_track' => env('TASK_AUTO_TIME_TRACKING', false),
        'round_to_minutes' => 15,
        'max_hours_per_day' => 24,
        'require_time_logs' => false,
    ],

    // 任务标签配置
    'tags' => [
        'enabled' => true,
        'max_tags_per_task' => 10,
        'predefined_tags' => [
            'urgent',
            'bug',
            'feature',
            'improvement',
            'documentation',
            'testing',
            'review',
            'deployment',
            'maintenance',
            'research',
        ],
        'allow_custom_tags' => true,
    ],

    // 任务模板配置
    'templates' => [
        'bug_fix' => [
            'name' => 'Bug Fix',
            'type' => 'bug',
            'priority' => 'high',
            'default_sub_tasks' => [
                'Reproduce the bug',
                'Identify root cause',
                'Implement fix',
                'Write tests',
                'Test fix',
                'Deploy fix',
            ],
        ],
        'feature_development' => [
            'name' => 'Feature Development',
            'type' => 'feature',
            'priority' => 'medium',
            'default_sub_tasks' => [
                'Requirements analysis',
                'Design specification',
                'Implementation',
                'Unit testing',
                'Integration testing',
                'Documentation',
                'Code review',
                'Deployment',
            ],
        ],
        'code_review' => [
            'name' => 'Code Review',
            'type' => 'improvement',
            'priority' => 'medium',
            'default_sub_tasks' => [
                'Review code changes',
                'Check coding standards',
                'Verify tests',
                'Provide feedback',
                'Approve or request changes',
            ],
        ],
    ],

    // 任务集成配置
    'integrations' => [
        'github' => [
            'enabled' => env('TASK_GITHUB_INTEGRATION', true),
            'auto_create_issues' => env('TASK_GITHUB_AUTO_CREATE', false),
            'sync_status' => env('TASK_GITHUB_SYNC_STATUS', false),
        ],
        'jira' => [
            'enabled' => env('TASK_JIRA_INTEGRATION', false),
            'auto_sync' => env('TASK_JIRA_AUTO_SYNC', false),
        ],
        'slack' => [
            'enabled' => env('TASK_SLACK_INTEGRATION', false),
            'notify_on_status_change' => env('TASK_SLACK_NOTIFY_STATUS', false),
        ],
    ],

    // 任务报告配置
    'reporting' => [
        'enabled' => env('TASK_REPORTING_ENABLED', true),
        'generate_daily_reports' => false,
        'generate_weekly_reports' => true,
        'generate_monthly_reports' => true,
        'include_time_tracking' => true,
        'include_completion_rates' => true,
        'include_agent_performance' => true,
    ],
];
