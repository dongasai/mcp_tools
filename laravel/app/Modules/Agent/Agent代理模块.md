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

### 5. Agent交互功能
- Agent向人类用户提问
- 问题类型管理（选择类、反馈类）
- 问题优先级控制
- 实时通知和状态跟踪

## 职责边界

### ✅ Agent模块负责
- AI Agent的注册、认证和管理
- Agent访问令牌的生成和验证
- Agent对项目和资源的权限控制
- Agent会话管理和状态跟踪
- Agent之间的协作和冲突解决
- 为MCP协议提供Agent身份验证
- Agent向人类用户的提问功能

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
│   ├── AgentQuestion.php          # Agent提问模型
│   └── AgentAuditLog.php          # Agent审计日志模型
├── Services/
│   ├── AgentService.php           # Agent核心服务
│   ├── AuthenticationService.php  # 认证服务
│   ├── AuthorizationService.php   # 授权服务
│   ├── PermissionService.php      # 权限管理服务
│   ├── SessionService.php         # 会话管理服务
│   ├── QuestionService.php        # 提问管理服务
│   ├── QuestionNotificationService.php # 提问通知服务
│   └── QuestionAnalyticsService.php # 提问分析服务
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
│   ├── AgentSessionExpired.php    # 会话过期事件
│   ├── QuestionCreated.php        # 问题创建事件
│   ├── QuestionAnswered.php       # 问题回答事件
│   └── QuestionIgnored.php        # 问题忽略事件
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

AgentService 负责管理 Agent 的生命周期，包括注册、状态更新、令牌生成和验证等功能。

### 2. AuthenticationService

AuthenticationService 处理 Agent 的认证逻辑，包括令牌验证、刷新和撤销等操作。

### 3. AuthorizationService

AuthorizationService 提供权限验证功能，确保 Agent 只能访问其授权的项目和资源。

### 4. PermissionService

PermissionService 定义和管理权限常量，并提供权限验证和计算功能。

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
## Agent模型扩展

### Agent权限模型

Agent权限模型用于管理Agent的权限，包括权限的授予、验证和范围检查。

### Agent会话模型

Agent会话模型用于管理Agent的会话状态，包括会话的创建、更新和终止。

## 权限策略

### Agent访问策略

Agent访问策略定义了用户对Agent的查看、更新、删除和权限管理权限。

### 项目访问策略

项目访问策略定义了Agent对项目的访问权限和操作权限。

## 命令行工具

### 注册Agent命令

注册Agent命令用于通过命令行注册新的Agent，并生成访问令牌。

## 事件和监听器

### Agent事件

Agent事件包括注册、认证和权限授予等关键操作的事件定义。

### 事件监听器

事件监听器用于响应Agent事件，如记录活动日志和更新Agent状态。

## 中间件

### Agent认证中间件

Agent认证中间件用于验证Agent的访问令牌并管理会话状态。

## 配置管理

配置管理定义了Agent模块的令牌、会话、权限和速率限制等关键参数。

**相关文档**：
- [Agent提问功能设计](./docs/Agent提问功能设计.md)
- [MCP协议模块](./MCP协议概述.md)
- [项目模块](./project.md)
- [任务模块](./task.md)
