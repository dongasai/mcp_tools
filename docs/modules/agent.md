# Agent 代理模块

## 概述

Agent代理模块负责管理AI Agent的完整生命周期，包括注册、认证、权限控制、状态管理等。该模块是MCP Tools系统中Agent管理的核心，确保每个Agent都有明确的身份标识和访问权限。Agent模块与User模块协作，但专注于AI Agent的管理，不直接处理人类用户的管理。

## 职责范围

### 1. Agent生命周期管理
- Agent注册和注销
- Agent状态监控
- Agent配置管理
- Agent性能统计

### 2. 身份认证与授权
- 访问令牌管理
- 权限验证机制
- 角色权限控制
- 安全审计日志

### 3. 项目访问控制
- 项目级权限管理
- 资源访问控制
- 操作权限验证
- 动态权限调整

### 4. Agent协作管理
- 多Agent协调
- 任务分配策略
- 冲突检测和解决
- 负载均衡

## 职责边界

### ✅ Agent模块负责
- AI Agent的注册、认证和管理
- Agent访问令牌的生成和验证
- Agent对项目和资源的权限控制
- Agent会话管理和状态跟踪
- Agent之间的协作和冲突解决
- 为MCP协议提供Agent身份验证

### ❌ Agent模块不负责
- 人类用户的账户管理（由User模块处理）
- 人类用户的登录认证（由User模块处理）
- MCP协议的具体实现（由MCP模块处理）
- 具体的业务逻辑执行（委托给业务模块）

### 🔄 与其他模块的协作
- **User模块**：获取Agent所属用户的信息和权限
- **MCP模块**：为MCP连接提供Agent认证服务
- **Project模块**：验证Agent对项目的访问权限
- **Task模块**：验证Agent对任务的操作权限

## 目录结构

```
app/Modules/Agent/
├── Models/
│   ├── Agent.php                  # Agent模型
│   ├── AgentPermission.php        # Agent权限模型
│   ├── AgentSession.php           # Agent会话模型
│   └── AgentAuditLog.php          # Agent审计日志模型
├── Services/
│   ├── AgentService.php           # Agent核心服务
│   ├── AuthenticationService.php  # 认证服务
│   ├── AuthorizationService.php   # 授权服务
│   ├── PermissionService.php      # 权限管理服务
│   └── SessionService.php         # 会话管理服务
├── Commands/
│   ├── RegisterAgentCommand.php   # 注册Agent命令
│   ├── ListAgentsCommand.php      # 列出Agent命令
│   ├── RevokeAgentCommand.php     # 撤销Agent命令
│   └── GrantPermissionCommand.php # 授权命令
├── Policies/
│   ├── AgentPolicy.php            # Agent访问策略
│   ├── ProjectAccessPolicy.php    # 项目访问策略
│   └── ResourceAccessPolicy.php   # 资源访问策略
├── Events/
│   ├── AgentRegistered.php        # Agent注册事件
│   ├── AgentAuthenticated.php     # Agent认证事件
│   ├── PermissionGranted.php      # 权限授予事件
│   ├── PermissionRevoked.php      # 权限撤销事件
│   └── AgentSessionExpired.php    # 会话过期事件
├── Listeners/
│   ├── LogAgentActivity.php       # 记录Agent活动
│   ├── UpdateAgentStatus.php      # 更新Agent状态
│   └── NotifyPermissionChange.php # 通知权限变更
├── Middleware/
│   ├── AgentAuthMiddleware.php    # Agent认证中间件
│   ├── PermissionMiddleware.php   # 权限检查中间件
│   └── SessionMiddleware.php      # 会话管理中间件
├── Contracts/
│   ├── AgentServiceInterface.php  # Agent服务接口
│   ├── AuthServiceInterface.php   # 认证服务接口
│   └── PermissionInterface.php    # 权限接口
└── Exceptions/
    ├── AgentNotFoundException.php  # Agent未找到异常
    ├── AuthenticationException.php # 认证异常
    ├── AuthorizationException.php  # 授权异常
    └── PermissionDeniedException.php # 权限拒绝异常
```

## 核心服务

### 1. AgentService

```php
<?php

namespace App\Modules\Agent\Services;

use App\Modules\Agent\Contracts\AgentServiceInterface;

class AgentService implements AgentServiceInterface
{
    /**
     * 注册新Agent
     */
    public function register(array $data): Agent;

    /**
     * 获取Agent信息
     */
    public function getAgent(string $agentId): ?Agent;

    /**
     * 更新Agent状态
     */
    public function updateStatus(string $agentId, string $status): bool;

    /**
     * 更新最后活跃时间
     */
    public function updateLastActive(string $agentId): bool;

    /**
     * 获取在线Agent列表
     */
    public function getOnlineAgents(): Collection;

    /**
     * 撤销Agent
     */
    public function revoke(string $agentId, string $reason = ''): bool;

    /**
     * 生成访问令牌
     */
    public function generateAccessToken(string $agentId): string;

    /**
     * 验证访问令牌
     */
    public function validateToken(string $token): ?Agent;
}
```

### 2. AuthenticationService

```php
<?php

namespace App\Modules\Agent\Services;

class AuthenticationService
{
    /**
     * 认证Agent
     */
    public function authenticate(string $token, string $agentId = null): Agent;

    /**
     * 验证令牌
     */
    public function validateToken(string $token): bool;

    /**
     * 刷新令牌
     */
    public function refreshToken(string $agentId): string;

    /**
     * 撤销令牌
     */
    public function revokeToken(string $token): bool;

    /**
     * 检查令牌是否过期
     */
    public function isTokenExpired(string $token): bool;

    /**
     * 获取令牌信息
     */
    public function getTokenInfo(string $token): array;
}
```

### 3. AuthorizationService

```php
<?php

namespace App\Modules\Agent\Services;

class AuthorizationService
{
    /**
     * 检查项目访问权限
     */
    public function canAccessProject(Agent $agent, int $projectId): bool;

    /**
     * 检查操作权限
     */
    public function canPerformAction(Agent $agent, string $action): bool;

    /**
     * 检查资源访问权限
     */
    public function canAccessResource(Agent $agent, string $resource): bool;

    /**
     * 获取Agent权限列表
     */
    public function getPermissions(Agent $agent): array;

    /**
     * 授予权限
     */
    public function grantPermission(Agent $agent, string $permission, array $scope = []): bool;

    /**
     * 撤销权限
     */
    public function revokePermission(Agent $agent, string $permission): bool;

    /**
     * 检查权限范围
     */
    public function checkScope(Agent $agent, string $permission, array $context): bool;
}
```

### 4. PermissionService

```php
<?php

namespace App\Modules\Agent\Services;

class PermissionService
{
    /**
     * 定义权限常量
     */
    public const PERMISSIONS = [
        'read' => 'Read access to resources',
        'create_task' => 'Create new tasks',
        'update_task' => 'Update existing tasks',
        'delete_task' => 'Delete tasks',
        'claim_task' => 'Claim tasks for execution',
        'complete_task' => 'Mark tasks as completed',
        'manage_project' => 'Manage project settings',
        'sync_github' => 'Sync with GitHub repositories',
        'create_github_issue' => 'Create GitHub issues',
        'update_github_issue' => 'Update GitHub issues',
        'admin' => 'Administrative access',
    ];

    /**
     * 获取所有可用权限
     */
    public function getAvailablePermissions(): array;

    /**
     * 验证权限名称
     */
    public function isValidPermission(string $permission): bool;

    /**
     * 获取权限层次结构
     */
    public function getPermissionHierarchy(): array;

    /**
     * 检查权限依赖
     */
    public function checkPermissionDependencies(array $permissions): array;

    /**
     * 计算有效权限
     */
    public function calculateEffectivePermissions(Agent $agent): array;
}
```

## Agent模型扩展

### Agent权限模型

```php
<?php

namespace App\Modules\Agent\Models;

class AgentPermission extends Model
{
    protected $fillable = [
        'agent_id',
        'permission',
        'scope',
        'granted_by',
        'granted_at',
        'expires_at',
    ];

    protected $casts = [
        'scope' => 'array',
        'granted_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * 检查权限是否过期
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * 检查权限范围
     */
    public function matchesScope(array $context): bool
    {
        if (empty($this->scope)) {
            return true; // 无限制
        }

        foreach ($this->scope as $key => $value) {
            if (!isset($context[$key]) || $context[$key] !== $value) {
                return false;
            }
        }

        return true;
    }
}
```

### Agent会话模型

```php
<?php

namespace App\Modules\Agent\Models;

class AgentSession extends Model
{
    protected $fillable = [
        'agent_id',
        'session_id',
        'ip_address',
        'user_agent',
        'started_at',
        'last_activity',
        'expires_at',
        'is_active',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'last_activity' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * 更新会话活动时间
     */
    public function updateActivity(): void
    {
        $this->update(['last_activity' => now()]);
    }

    /**
     * 检查会话是否过期
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * 终止会话
     */
    public function terminate(): void
    {
        $this->update(['is_active' => false]);
    }
}
```

## 权限策略

### Agent访问策略

```php
<?php

namespace App\Modules\Agent\Policies;

class AgentPolicy
{
    /**
     * 检查是否可以查看Agent
     */
    public function view(User $user, Agent $agent): bool
    {
        return $user->id === $agent->user_id || $user->hasRole('admin');
    }

    /**
     * 检查是否可以更新Agent
     */
    public function update(User $user, Agent $agent): bool
    {
        return $user->id === $agent->user_id || $user->hasRole('admin');
    }

    /**
     * 检查是否可以删除Agent
     */
    public function delete(User $user, Agent $agent): bool
    {
        return $user->id === $agent->user_id || $user->hasRole('admin');
    }

    /**
     * 检查是否可以管理Agent权限
     */
    public function managePermissions(User $user, Agent $agent): bool
    {
        return $user->hasRole('admin') ||
               ($user->id === $agent->user_id && $user->hasPermission('manage_agent_permissions'));
    }
}
```

### 项目访问策略

```php
<?php

namespace App\Modules\Agent\Policies;

class ProjectAccessPolicy
{
    /**
     * 检查Agent是否可以访问项目
     */
    public function canAccess(Agent $agent, Project $project): bool
    {
        // 检查Agent是否有项目访问权限
        if (!in_array($project->id, $agent->allowed_projects ?? [])) {
            return false;
        }

        // 检查项目状态
        if ($project->status !== 'active') {
            return false;
        }

        // 检查Agent状态
        if ($agent->status !== 'active') {
            return false;
        }

        return true;
    }

    /**
     * 检查Agent是否可以执行项目操作
     */
    public function canPerformAction(Agent $agent, Project $project, string $action): bool
    {
        if (!$this->canAccess($agent, $project)) {
            return false;
        }

        return in_array($action, $agent->allowed_actions ?? []);
    }
}
```

## 命令行工具

### 注册Agent命令

```php
<?php

namespace App\Modules\Agent\Commands;

class RegisterAgentCommand extends Command
{
    protected $signature = 'agent:register
                            {--name= : Agent name}
                            {--type= : Agent type}
                            {--user-id= : User ID}
                            {--projects= : Allowed project IDs}
                            {--permissions= : Allowed permissions}
                            {--expires-in= : Token expiration time in seconds}';

    protected $description = 'Register a new Agent';

    public function handle(AgentService $agentService): int
    {
        $data = [
            'name' => $this->option('name') ?: $this->ask('Agent name'),
            'type' => $this->option('type') ?: $this->ask('Agent type'),
            'user_id' => $this->option('user-id') ?: $this->ask('User ID'),
            'allowed_projects' => $this->parseProjects($this->option('projects')),
            'allowed_actions' => $this->parsePermissions($this->option('permissions')),
            'token_expires_in' => $this->option('expires-in') ?: 86400,
        ];

        try {
            $agent = $agentService->register($data);
            $token = $agentService->generateAccessToken($agent->agent_id);

            $this->info("Agent registered successfully!");
            $this->table(['Field', 'Value'], [
                ['Agent ID', $agent->agent_id],
                ['Name', $agent->name],
                ['Type', $agent->type],
                ['Access Token', $token],
                ['Allowed Projects', implode(', ', $agent->allowed_projects ?? [])],
                ['Permissions', implode(', ', $agent->allowed_actions ?? [])],
            ]);

            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to register agent: " . $e->getMessage());
            return 1;
        }
    }
}
```

## 事件和监听器

### Agent事件

```php
<?php

namespace App\Modules\Agent\Events;

class AgentRegistered
{
    public function __construct(
        public readonly Agent $agent,
        public readonly User $registeredBy,
        public readonly \DateTime $timestamp
    ) {}
}

class AgentAuthenticated
{
    public function __construct(
        public readonly Agent $agent,
        public readonly string $ipAddress,
        public readonly string $userAgent,
        public readonly \DateTime $timestamp
    ) {}
}

class PermissionGranted
{
    public function __construct(
        public readonly Agent $agent,
        public readonly string $permission,
        public readonly array $scope,
        public readonly User $grantedBy,
        public readonly \DateTime $timestamp
    ) {}
}
```

### 事件监听器

```php
<?php

namespace App\Modules\Agent\Listeners;

class LogAgentActivity
{
    public function handle(AgentAuthenticated $event): void
    {
        AgentAuditLog::create([
            'agent_id' => $event->agent->agent_id,
            'action' => 'authenticated',
            'ip_address' => $event->ipAddress,
            'user_agent' => $event->userAgent,
            'timestamp' => $event->timestamp,
        ]);
    }
}

class UpdateAgentStatus
{
    public function handle(AgentAuthenticated $event): void
    {
        $event->agent->updateLastActive();
    }
}
```

## 中间件

### Agent认证中间件

```php
<?php

namespace App\Modules\Agent\Middleware;

class AgentAuthMiddleware
{
    public function handle(Request $request, \Closure $next)
    {
        $token = $request->bearerToken() ?: $request->query('token');
        $agentId = $request->header('Agent-ID') ?: $request->query('agent_id');

        if (!$token) {
            throw new AuthenticationException('Access token required');
        }

        $authService = app(AuthenticationService::class);

        try {
            $agent = $authService->authenticate($token, $agentId);
            $request->setAgent($agent);

            // 更新最后活跃时间
            $agent->updateLastActive();

            return $next($request);
        } catch (\Exception $e) {
            throw new AuthenticationException('Invalid access token');
        }
    }
}
```

## 配置管理

```php
// config/agent.php
return [
    'token' => [
        'default_expiry' => env('AGENT_TOKEN_EXPIRY', 86400),
        'refresh_threshold' => env('AGENT_TOKEN_REFRESH_THRESHOLD', 3600),
        'max_tokens_per_agent' => env('AGENT_MAX_TOKENS', 5),
    ],

    'session' => [
        'timeout' => env('AGENT_SESSION_TIMEOUT', 1800),
        'max_sessions_per_agent' => env('AGENT_MAX_SESSIONS', 3),
        'cleanup_interval' => env('AGENT_SESSION_CLEANUP', 300),
    ],

    'permissions' => [
        'default' => ['read'],
        'admin_required' => ['admin', 'manage_project'],
        'inheritance_enabled' => env('AGENT_PERMISSION_INHERITANCE', true),
    ],

    'rate_limiting' => [
        'enabled' => env('AGENT_RATE_LIMITING', true),
        'requests_per_minute' => env('AGENT_RATE_LIMIT', 60),
        'burst_limit' => env('AGENT_BURST_LIMIT', 10),
    ],
];
```

---

**相关文档**：
- [MCP协议模块](./mcp.md)
- [项目模块](./project.md)
- [任务模块](./task.md)
