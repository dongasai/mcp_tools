<?php

return [
    /*
    |--------------------------------------------------------------------------
    | 用户注册配置
    |--------------------------------------------------------------------------
    */
    'registration' => [
        'enabled' => env('USER_REGISTRATION_ENABLED', true),
        'email_verification_required' => env('USER_EMAIL_VERIFICATION_REQUIRED', true),
        'auto_activate' => env('USER_AUTO_ACTIVATE', false),
        'default_role' => env('USER_DEFAULT_ROLE', 'user'),
        'allowed_domains' => env('USER_ALLOWED_DOMAINS', ''), // 逗号分隔的域名列表
    ],

    /*
    |--------------------------------------------------------------------------
    | 用户认证配置
    |--------------------------------------------------------------------------
    */
    'authentication' => [
        'max_login_attempts' => env('USER_MAX_LOGIN_ATTEMPTS', 5),
        'lockout_duration' => env('USER_LOCKOUT_DURATION', 900), // 15分钟
        'session_lifetime' => env('USER_SESSION_LIFETIME', 120), // 2小时
        'remember_token_lifetime' => env('USER_REMEMBER_TOKEN_LIFETIME', 43200), // 30天
    ],

    /*
    |--------------------------------------------------------------------------
    | 密码配置
    |--------------------------------------------------------------------------
    */
    'password' => [
        'min_length' => env('USER_PASSWORD_MIN_LENGTH', 8),
        'require_uppercase' => env('USER_PASSWORD_REQUIRE_UPPERCASE', true),
        'require_lowercase' => env('USER_PASSWORD_REQUIRE_LOWERCASE', true),
        'require_numbers' => env('USER_PASSWORD_REQUIRE_NUMBERS', true),
        'require_symbols' => env('USER_PASSWORD_REQUIRE_SYMBOLS', false),
        'reset_token_lifetime' => env('USER_PASSWORD_RESET_TOKEN_LIFETIME', 3600), // 1小时
    ],

    /*
    |--------------------------------------------------------------------------
    | 头像配置
    |--------------------------------------------------------------------------
    */
    'avatar' => [
        'max_size' => env('USER_AVATAR_MAX_SIZE', 2048), // KB
        'allowed_types' => ['jpg', 'jpeg', 'png', 'gif'],
        'storage_disk' => env('USER_AVATAR_STORAGE_DISK', 'public'),
        'storage_path' => env('USER_AVATAR_STORAGE_PATH', 'avatars'),
        'default_gravatar' => env('USER_DEFAULT_GRAVATAR', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | 用户权限配置
    |--------------------------------------------------------------------------
    */
    'permissions' => [
        'super_admin' => [
            'user.manage',
            'user.create',
            'user.update',
            'user.delete',
            'user.view_all',
            'system.manage',
            'config.manage',
        ],
        'admin' => [
            'user.view_all',
            'user.update',
            'project.manage',
            'task.manage',
            'agent.manage',
        ],
        'user' => [
            'profile.update',
            'project.create',
            'project.manage_own',
            'task.create',
            'task.manage_own',
            'agent.register',
            'agent.manage_own',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 通知配置
    |--------------------------------------------------------------------------
    */
    'notifications' => [
        'welcome_email' => env('USER_SEND_WELCOME_EMAIL', true),
        'email_verification' => env('USER_SEND_EMAIL_VERIFICATION', true),
        'password_reset' => env('USER_SEND_PASSWORD_RESET', true),
        'status_change' => env('USER_NOTIFY_STATUS_CHANGE', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | 用户设置默认值
    |--------------------------------------------------------------------------
    */
    'default_settings' => [
        'theme' => 'light',
        'language' => 'en',
        'timezone' => 'UTC',
        'notifications' => [
            'email' => true,
            'browser' => true,
            'task_assigned' => true,
            'task_completed' => true,
            'project_updates' => true,
            'agent_notifications' => true,
        ],
        'privacy' => [
            'profile_public' => false,
            'show_email' => false,
            'show_last_login' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 用户限制配置
    |--------------------------------------------------------------------------
    */
    'limits' => [
        'max_projects' => env('USER_MAX_PROJECTS', 10),
        'max_agents' => env('USER_MAX_AGENTS', 5),
        'max_tasks_per_project' => env('USER_MAX_TASKS_PER_PROJECT', 100),
        'max_file_uploads' => env('USER_MAX_FILE_UPLOADS', 50),
        'max_storage_size' => env('USER_MAX_STORAGE_SIZE', 1024), // MB
    ],

    /*
    |--------------------------------------------------------------------------
    | 数据清理配置
    |--------------------------------------------------------------------------
    */
    'cleanup' => [
        'soft_deleted_users_retention' => env('USER_SOFT_DELETED_RETENTION', 90), // 天
        'inactive_users_threshold' => env('USER_INACTIVE_THRESHOLD', 365), // 天
        'unverified_users_retention' => env('USER_UNVERIFIED_RETENTION', 30), // 天
    ],
];
