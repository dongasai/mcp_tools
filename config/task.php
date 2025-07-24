<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Task Module Configuration
    |--------------------------------------------------------------------------
    |
    | 任务模块的配置选项
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    |
    | 控制各种任务事件的通知设置
    |
    */
    'notifications' => [
        // 任务创建通知
        'task_created' => env('TASK_NOTIFY_CREATED', true),
        
        // 任务状态变更通知
        'status_changed' => env('TASK_NOTIFY_STATUS_CHANGED', true),
        
        // 任务进度更新通知
        'progress_updated' => env('TASK_NOTIFY_PROGRESS_UPDATED', false),
        
        // Agent变更通知
        'agent_changed' => env('TASK_NOTIFY_AGENT_CHANGED', true),
        
        // 评论创建通知
        'comment_created' => env('TASK_NOTIFY_COMMENT_CREATED', true),
        
        // 评论更新通知
        'comment_updated' => env('TASK_NOTIFY_COMMENT_UPDATED', false),
        
        // 评论删除通知
        'comment_deleted' => env('TASK_NOTIFY_COMMENT_DELETED', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Automation
    |--------------------------------------------------------------------------
    |
    | 任务自动化处理设置
    |
    */
    'automation' => [
        // 自动开始子任务
        'auto_start_sub_tasks' => env('TASK_AUTO_START_SUB_TASKS', false),
        
        // 自动完成父任务
        'auto_complete_parent_task' => env('TASK_AUTO_COMPLETE_PARENT', true),
        
        // 自动更新进度
        'auto_update_progress' => env('TASK_AUTO_UPDATE_PROGRESS', true),
        
        // 自动分配Agent给子任务
        'auto_assign_to_agent' => env('TASK_AUTO_ASSIGN_AGENT', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Progress Milestones
    |--------------------------------------------------------------------------
    |
    | 进度里程碑设置
    |
    */
    'progress_milestones' => [
        'quarter_complete' => 25,
        'half_complete' => 50,
        'three_quarters_complete' => 75,
        'fully_complete' => 100,
    ],

    /*
    |--------------------------------------------------------------------------
    | Task Limits
    |--------------------------------------------------------------------------
    |
    | 任务限制设置
    |
    */
    'limits' => [
        // 每个用户最大任务数
        'max_tasks_per_user' => env('TASK_MAX_PER_USER', 100),
        
        // 每个项目最大任务数
        'max_tasks_per_project' => env('TASK_MAX_PER_PROJECT', 1000),
        
        // 最大子任务层级
        'max_sub_task_depth' => env('TASK_MAX_SUB_DEPTH', 5),
        
        // 每个任务最大评论数
        'max_comments_per_task' => env('TASK_MAX_COMMENTS', 500),
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Values
    |--------------------------------------------------------------------------
    |
    | 默认值设置
    |
    */
    'defaults' => [
        // 默认任务类型
        'task_type' => 'main',
        
        // 默认任务状态
        'task_status' => 'pending',
        
        // 默认任务优先级
        'task_priority' => 'medium',
        
        // 默认进度
        'progress' => 0,
        
        // 默认评论类型
        'comment_type' => 'general',
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    |
    | 缓存设置
    |
    */
    'cache' => [
        // 任务统计缓存时间（分钟）
        'statistics_ttl' => env('TASK_CACHE_STATISTICS_TTL', 60),
        
        // 任务列表缓存时间（分钟）
        'task_list_ttl' => env('TASK_CACHE_LIST_TTL', 10),
        
        // 启用缓存
        'enabled' => env('TASK_CACHE_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | 日志设置
    |
    */
    'logging' => [
        // 启用任务操作日志
        'enabled' => env('TASK_LOGGING_ENABLED', true),
        
        // 日志级别
        'level' => env('TASK_LOGGING_LEVEL', 'info'),
        
        // 日志渠道
        'channel' => env('TASK_LOGGING_CHANNEL', 'daily'),
    ],

    /*
    |--------------------------------------------------------------------------
    | MCP Integration
    |--------------------------------------------------------------------------
    |
    | MCP集成设置
    |
    */
    'mcp' => [
        // 启用MCP集成
        'enabled' => env('TASK_MCP_ENABLED', true),
        
        // Agent认证
        'agent_auth_required' => env('TASK_MCP_AGENT_AUTH', true),
        
        // 操作权限验证
        'permission_check' => env('TASK_MCP_PERMISSION_CHECK', true),
        
        // 请求限制（每分钟）
        'rate_limit' => env('TASK_MCP_RATE_LIMIT', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | UI Settings
    |--------------------------------------------------------------------------
    |
    | 用户界面设置
    |
    */
    'ui' => [
        // 每页显示任务数
        'tasks_per_page' => env('TASK_UI_PER_PAGE', 20),
        
        // 每页显示评论数
        'comments_per_page' => env('TASK_UI_COMMENTS_PER_PAGE', 10),
        
        // 启用实时更新
        'real_time_updates' => env('TASK_UI_REAL_TIME', false),
        
        // 默认排序
        'default_sort' => env('TASK_UI_DEFAULT_SORT', 'created_at'),
        
        // 默认排序方向
        'default_order' => env('TASK_UI_DEFAULT_ORDER', 'desc'),
    ],
];
