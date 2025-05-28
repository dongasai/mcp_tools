# Project 项目模块

## 概述

Project项目模块负责管理开发项目的完整生命周期，包括项目创建、配置管理、成员管理、仓库关联等功能。该模块是MCP Tools系统中项目管理的核心，为任务管理和Agent协作提供基础支撑。

## 职责范围

### 1. 项目生命周期管理
- 项目创建和初始化
- 项目配置管理
- 项目状态跟踪
- 项目归档和删除

### 2. 仓库集成管理
- GitHub仓库关联
- 多仓库项目支持
- 仓库权限管理
- 代码同步配置

### 3. 成员和权限管理
- 项目成员管理
- 角色权限分配
- Agent访问控制
- 协作权限设置

### 4. 项目统计和分析
- 项目进度统计
- 任务完成率分析
- 成员贡献统计
- 性能指标监控

## 目录结构

```
app/Modules/Project/
├── Models/
│   ├── Project.php                # 项目模型
│   ├── ProjectMember.php          # 项目成员模型
│   ├── ProjectRepository.php      # 项目仓库模型
│   └── ProjectSetting.php         # 项目设置模型
├── Services/
│   ├── ProjectService.php         # 项目核心服务
│   ├── MemberService.php          # 成员管理服务
│   ├── RepositoryService.php      # 仓库管理服务
│   ├── SettingService.php         # 设置管理服务
│   └── StatisticsService.php      # 统计分析服务
├── Controllers/
│   ├── ProjectController.php      # 项目控制器
│   ├── MemberController.php       # 成员控制器
│   ├── RepositoryController.php   # 仓库控制器
│   └── StatisticsController.php   # 统计控制器
├── Resources/
│   ├── ProjectResource.php        # 项目API资源
│   ├── ProjectCollection.php      # 项目集合资源
│   ├── MemberResource.php         # 成员API资源
│   └── StatisticsResource.php     # 统计API资源
├── Requests/
│   ├── CreateProjectRequest.php   # 创建项目请求
│   ├── UpdateProjectRequest.php   # 更新项目请求
│   ├── AddMemberRequest.php       # 添加成员请求
│   └── AddRepositoryRequest.php   # 添加仓库请求
├── Events/
│   ├── ProjectCreated.php         # 项目创建事件
│   ├── ProjectUpdated.php         # 项目更新事件
│   ├── MemberAdded.php            # 成员添加事件
│   ├── MemberRemoved.php          # 成员移除事件
│   └── RepositoryAdded.php        # 仓库添加事件
├── Listeners/
│   ├── CreateDefaultTasks.php     # 创建默认任务
│   ├── NotifyMembers.php          # 通知成员
│   └── UpdateStatistics.php       # 更新统计
├── Policies/
│   ├── ProjectPolicy.php          # 项目访问策略
│   └── MemberPolicy.php           # 成员管理策略
└── Observers/
    └── ProjectObserver.php         # 项目模型观察者
```

## 核心服务

### 1. ProjectService

```php
<?php

namespace App\Modules\Project\Services;

class ProjectService
{
    /**
     * 创建新项目
     */
    public function create(array $data, User $user): Project;
    
    /**
     * 更新项目信息
     */
    public function update(Project $project, array $data): Project;
    
    /**
     * 获取用户项目列表
     */
    public function getUserProjects(User $user, array $filters = []): Collection;
    
    /**
     * 获取Agent可访问的项目
     */
    public function getAgentProjects(Agent $agent): Collection;
    
    /**
     * 归档项目
     */
    public function archive(Project $project): bool;
    
    /**
     * 恢复项目
     */
    public function restore(Project $project): bool;
    
    /**
     * 删除项目
     */
    public function delete(Project $project): bool;
    
    /**
     * 复制项目
     */
    public function duplicate(Project $project, array $options = []): Project;
    
    /**
     * 获取项目统计信息
     */
    public function getStatistics(Project $project): array;
}
```

### 2. MemberService

```php
<?php

namespace App\Modules\Project\Services;

class MemberService
{
    /**
     * 添加项目成员
     */
    public function addMember(Project $project, User $user, string $role = 'member'): ProjectMember;
    
    /**
     * 移除项目成员
     */
    public function removeMember(Project $project, User $user): bool;
    
    /**
     * 更新成员角色
     */
    public function updateMemberRole(ProjectMember $member, string $role): bool;
    
    /**
     * 获取项目成员列表
     */
    public function getMembers(Project $project): Collection;
    
    /**
     * 检查用户是否为项目成员
     */
    public function isMember(Project $project, User $user): bool;
    
    /**
     * 获取用户在项目中的角色
     */
    public function getUserRole(Project $project, User $user): ?string;
    
    /**
     * 批量添加成员
     */
    public function addMembers(Project $project, array $users, string $role = 'member'): Collection;
    
    /**
     * 邀请成员加入项目
     */
    public function inviteMember(Project $project, string $email, string $role = 'member'): ProjectInvitation;
}
```

### 3. RepositoryService

```php
<?php

namespace App\Modules\Project\Services;

class RepositoryService
{
    /**
     * 添加仓库到项目
     */
    public function addRepository(Project $project, string $repositoryUrl, array $config = []): ProjectRepository;
    
    /**
     * 移除项目仓库
     */
    public function removeRepository(Project $project, string $repositoryUrl): bool;
    
    /**
     * 更新仓库配置
     */
    public function updateRepository(ProjectRepository $repository, array $config): bool;
    
    /**
     * 获取项目仓库列表
     */
    public function getRepositories(Project $project): Collection;
    
    /**
     * 验证仓库访问权限
     */
    public function validateRepositoryAccess(string $repositoryUrl, string $token): bool;
    
    /**
     * 同步仓库信息
     */
    public function syncRepository(ProjectRepository $repository): bool;
    
    /**
     * 获取仓库统计信息
     */
    public function getRepositoryStats(ProjectRepository $repository): array;
    
    /**
     * 检查仓库状态
     */
    public function checkRepositoryStatus(ProjectRepository $repository): string;
}
```

### 4. SettingService

```php
<?php

namespace App\Modules\Project\Services;

class SettingService
{
    /**
     * 获取项目设置
     */
    public function getSettings(Project $project): array;
    
    /**
     * 更新项目设置
     */
    public function updateSettings(Project $project, array $settings): bool;
    
    /**
     * 获取特定设置值
     */
    public function getSetting(Project $project, string $key, mixed $default = null): mixed;
    
    /**
     * 设置特定值
     */
    public function setSetting(Project $project, string $key, mixed $value): bool;
    
    /**
     * 重置设置为默认值
     */
    public function resetSettings(Project $project): bool;
    
    /**
     * 导出项目设置
     */
    public function exportSettings(Project $project): array;
    
    /**
     * 导入项目设置
     */
    public function importSettings(Project $project, array $settings): bool;
    
    /**
     * 验证设置格式
     */
    public function validateSettings(array $settings): array;
}
```

## 数据模型

### Project模型扩展

```php
<?php

namespace App\Modules\Project\Models;

class Project extends Model
{
    protected $fillable = [
        'name',
        'description',
        'timezone',
        'status',
        'settings',
        'user_id',
    ];
    
    protected $casts = [
        'settings' => 'array',
    ];
    
    /**
     * 项目状态常量
     */
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_ARCHIVED = 'archived';
    
    /**
     * 获取项目成员
     */
    public function members(): HasMany
    {
        return $this->hasMany(ProjectMember::class);
    }
    
    /**
     * 获取项目仓库
     */
    public function repositories(): HasMany
    {
        return $this->hasMany(ProjectRepository::class);
    }
    
    /**
     * 获取项目任务
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }
    
    /**
     * 获取活跃任务
     */
    public function activeTasks(): HasMany
    {
        return $this->tasks()->whereIn('status', ['pending', 'claimed', 'in_progress']);
    }
    
    /**
     * 获取已完成任务
     */
    public function completedTasks(): HasMany
    {
        return $this->tasks()->where('status', 'completed');
    }
    
    /**
     * 检查项目是否活跃
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }
    
    /**
     * 获取项目进度百分比
     */
    public function getProgressPercentage(): float
    {
        $totalTasks = $this->tasks()->count();
        if ($totalTasks === 0) {
            return 0;
        }
        
        $completedTasks = $this->completedTasks()->count();
        return round(($completedTasks / $totalTasks) * 100, 2);
    }
    
    /**
     * 获取项目设置
     */
    public function getSetting(string $key, mixed $default = null): mixed
    {
        return data_get($this->settings, $key, $default);
    }
    
    /**
     * 设置项目配置
     */
    public function setSetting(string $key, mixed $value): void
    {
        $settings = $this->settings ?? [];
        data_set($settings, $key, $value);
        $this->settings = $settings;
        $this->save();
    }
}
```

### ProjectMember模型

```php
<?php

namespace App\Modules\Project\Models;

class ProjectMember extends Model
{
    protected $fillable = [
        'project_id',
        'user_id',
        'role',
        'permissions',
        'joined_at',
    ];
    
    protected $casts = [
        'permissions' => 'array',
        'joined_at' => 'datetime',
    ];
    
    /**
     * 角色常量
     */
    public const ROLE_OWNER = 'owner';
    public const ROLE_ADMIN = 'admin';
    public const ROLE_MEMBER = 'member';
    public const ROLE_VIEWER = 'viewer';
    
    /**
     * 获取项目
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
    
    /**
     * 获取用户
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    /**
     * 检查是否有特定权限
     */
    public function hasPermission(string $permission): bool
    {
        $permissions = $this->permissions ?? [];
        return in_array($permission, $permissions) || $this->isOwnerOrAdmin();
    }
    
    /**
     * 检查是否为所有者或管理员
     */
    public function isOwnerOrAdmin(): bool
    {
        return in_array($this->role, [self::ROLE_OWNER, self::ROLE_ADMIN]);
    }
    
    /**
     * 获取角色权限
     */
    public function getRolePermissions(): array
    {
        return match($this->role) {
            self::ROLE_OWNER => ['*'], // 所有权限
            self::ROLE_ADMIN => [
                'manage_project', 'manage_members', 'manage_tasks',
                'manage_repositories', 'view_statistics'
            ],
            self::ROLE_MEMBER => [
                'view_project', 'create_task', 'update_task', 'claim_task'
            ],
            self::ROLE_VIEWER => ['view_project'],
            default => [],
        };
    }
}
```

### ProjectRepository模型

```php
<?php

namespace App\Modules\Project\Models;

class ProjectRepository extends Model
{
    protected $fillable = [
        'project_id',
        'repository_url',
        'repository_name',
        'provider',
        'config',
        'last_sync_at',
        'sync_status',
    ];
    
    protected $casts = [
        'config' => 'array',
        'last_sync_at' => 'datetime',
    ];
    
    /**
     * 提供商常量
     */
    public const PROVIDER_GITHUB = 'github';
    public const PROVIDER_GITLAB = 'gitlab';
    public const PROVIDER_BITBUCKET = 'bitbucket';
    
    /**
     * 同步状态常量
     */
    public const SYNC_STATUS_PENDING = 'pending';
    public const SYNC_STATUS_SYNCING = 'syncing';
    public const SYNC_STATUS_SUCCESS = 'success';
    public const SYNC_STATUS_FAILED = 'failed';
    
    /**
     * 获取项目
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
    
    /**
     * 获取仓库所有者和名称
     */
    public function getOwnerAndName(): array
    {
        if ($this->provider === self::PROVIDER_GITHUB) {
            preg_match('/github\.com\/([^\/]+)\/([^\/]+)/', $this->repository_url, $matches);
            return [
                'owner' => $matches[1] ?? null,
                'name' => $matches[2] ?? null,
            ];
        }
        
        return ['owner' => null, 'name' => null];
    }
    
    /**
     * 检查是否需要同步
     */
    public function needsSync(): bool
    {
        if (!$this->last_sync_at) {
            return true;
        }
        
        $syncInterval = $this->getConfig('sync_interval', 3600); // 默认1小时
        return $this->last_sync_at->addSeconds($syncInterval)->isPast();
    }
    
    /**
     * 获取配置值
     */
    public function getConfig(string $key, mixed $default = null): mixed
    {
        return data_get($this->config, $key, $default);
    }
    
    /**
     * 设置配置值
     */
    public function setConfig(string $key, mixed $value): void
    {
        $config = $this->config ?? [];
        data_set($config, $key, $value);
        $this->config = $config;
        $this->save();
    }
}
```

## API控制器

### ProjectController

```php
<?php

namespace App\Modules\Project\Controllers;

class ProjectController extends Controller
{
    public function __construct(
        private ProjectService $projectService
    ) {}
    
    /**
     * 获取项目列表
     */
    public function index(Request $request): JsonResponse
    {
        $projects = $this->projectService->getUserProjects(
            $request->user(),
            $request->only(['status', 'search', 'sort'])
        );
        
        return ProjectResource::collection($projects)->response();
    }
    
    /**
     * 创建项目
     */
    public function store(CreateProjectRequest $request): JsonResponse
    {
        $project = $this->projectService->create(
            $request->validated(),
            $request->user()
        );
        
        return new ProjectResource($project);
    }
    
    /**
     * 获取项目详情
     */
    public function show(Project $project): JsonResponse
    {
        $this->authorize('view', $project);
        
        return new ProjectResource($project->load(['members.user', 'repositories', 'tasks']));
    }
    
    /**
     * 更新项目
     */
    public function update(UpdateProjectRequest $request, Project $project): JsonResponse
    {
        $this->authorize('update', $project);
        
        $project = $this->projectService->update($project, $request->validated());
        
        return new ProjectResource($project);
    }
    
    /**
     * 删除项目
     */
    public function destroy(Project $project): JsonResponse
    {
        $this->authorize('delete', $project);
        
        $this->projectService->delete($project);
        
        return response()->json(['message' => 'Project deleted successfully']);
    }
    
    /**
     * 获取项目统计
     */
    public function statistics(Project $project): JsonResponse
    {
        $this->authorize('view', $project);
        
        $statistics = $this->projectService->getStatistics($project);
        
        return response()->json($statistics);
    }
}
```

## 事件和监听器

### 项目事件

```php
<?php

namespace App\Modules\Project\Events;

class ProjectCreated
{
    public function __construct(
        public readonly Project $project,
        public readonly User $createdBy
    ) {}
}

class MemberAdded
{
    public function __construct(
        public readonly Project $project,
        public readonly User $member,
        public readonly string $role,
        public readonly User $addedBy
    ) {}
}

class RepositoryAdded
{
    public function __construct(
        public readonly Project $project,
        public readonly ProjectRepository $repository,
        public readonly User $addedBy
    ) {}
}
```

### 事件监听器

```php
<?php

namespace App\Modules\Project\Listeners;

class CreateDefaultTasks
{
    public function handle(ProjectCreated $event): void
    {
        $project = $event->project;
        
        // 创建默认任务
        $defaultTasks = [
            [
                'title' => '项目初始化',
                'description' => '设置项目基础配置和文档',
                'priority' => 'medium',
                'status' => 'pending',
            ],
            [
                'title' => '环境搭建',
                'description' => '配置开发和测试环境',
                'priority' => 'high',
                'status' => 'pending',
            ],
        ];
        
        foreach ($defaultTasks as $taskData) {
            $project->tasks()->create($taskData);
        }
    }
}
```

## 配置管理

```php
// config/project.php
return [
    'defaults' => [
        'timezone' => env('PROJECT_DEFAULT_TIMEZONE', 'UTC'),
        'status' => 'active',
        'settings' => [
            'auto_assign_tasks' => false,
            'github_sync_enabled' => true,
            'notification_enabled' => true,
            'task_auto_close' => false,
        ],
    ],
    
    'limits' => [
        'max_repositories' => env('PROJECT_MAX_REPOSITORIES', 10),
        'max_members' => env('PROJECT_MAX_MEMBERS', 50),
        'max_tasks' => env('PROJECT_MAX_TASKS', 1000),
    ],
    
    'features' => [
        'repository_sync' => env('PROJECT_REPOSITORY_SYNC', true),
        'member_invitations' => env('PROJECT_MEMBER_INVITATIONS', true),
        'project_templates' => env('PROJECT_TEMPLATES', true),
        'advanced_statistics' => env('PROJECT_ADVANCED_STATS', false),
    ],
    
    'sync' => [
        'default_interval' => env('PROJECT_SYNC_INTERVAL', 3600),
        'max_sync_attempts' => env('PROJECT_MAX_SYNC_ATTEMPTS', 3),
        'sync_timeout' => env('PROJECT_SYNC_TIMEOUT', 300),
    ],
];
```

---

**相关文档**：
- [任务模块](./task.md)
- [GitHub模块](./github.md)
- [Agent模块](./agent.md)
