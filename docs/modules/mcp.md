# MCP 协议模块

## 概述

MCP协议模块是MCP Tools的核心通信层，负责实现Model Context Protocol (MCP) 1.0标准，提供基于SSE的实时通信服务。该模块专注于为AI Agent提供任务处理和资源访问的标准化接口，不涉及用户管理功能。

## 职责范围

### 1. 协议实现
- MCP 1.0协议标准实现
- JSON-RPC 2.0消息格式
- 协议版本协商
- 能力声明和发现

### 2. 传输层
- SSE (Server-Sent Events) 传输
- WebSocket传输支持
- STDIO传输支持
- HTTP长连接管理

### 3. 连接管理
- 客户端连接池
- 连接状态监控
- 心跳检测机制
- 自动重连处理

### 4. 消息路由
- 请求/响应路由
- 事件分发机制
- 消息队列管理
- 错误处理和重试

## 职责边界

### ✅ MCP模块负责
- 实现MCP 1.0协议标准
- 提供任务相关的Resources和Tools
- 管理Agent的MCP连接和会话
- 处理任务操作请求（创建、更新、查询等）
- 提供项目和GitHub资源的只读访问
- SSE实时数据推送
- 协议级别的错误处理

### ❌ MCP模块不负责
- 用户账户管理和认证
- 用户权限验证（由Agent模块处理）
- 业务逻辑验证（由具体业务模块处理）
- 数据持久化（通过业务模块调用）
- 用户界面和管理功能

### 🔄 与其他模块的协作
- **Agent模块**：验证Agent身份和权限
- **Task模块**：执行具体的任务操作
- **Project模块**：获取项目资源数据
- **GitHub模块**：获取GitHub资源数据

## 目录结构

```
app/Modules/Mcp/
├── Server/
│   ├── McpServer.php              # MCP服务器主类
│   ├── ConnectionManager.php      # 连接管理器
│   ├── MessageRouter.php          # 消息路由器
│   └── CapabilityManager.php      # 能力管理器
├── Transports/
│   ├── SseTransport.php           # SSE传输层
│   ├── WebSocketTransport.php     # WebSocket传输层
│   ├── StdioTransport.php         # STDIO传输层
│   └── HttpTransport.php          # HTTP传输层
├── Protocol/
│   ├── MessageParser.php          # 消息解析器
│   ├── ProtocolValidator.php      # 协议验证器
│   ├── RequestHandler.php         # 请求处理器
│   └── ResponseBuilder.php        # 响应构建器
├── Resources/
│   ├── ProjectResource.php        # 项目资源
│   ├── TaskResource.php           # 任务资源
│   ├── AgentResource.php          # Agent资源
│   └── GitHubResource.php         # GitHub资源
├── Tools/
│   ├── ProjectTool.php            # 项目工具
│   ├── TaskTool.php               # 任务工具
│   ├── AgentTool.php              # Agent工具
│   └── GitHubTool.php             # GitHub工具
├── Middleware/
│   ├── AuthenticationMiddleware.php # 认证中间件
│   ├── AuthorizationMiddleware.php  # 授权中间件
│   ├── RateLimitMiddleware.php      # 限流中间件
│   └── LoggingMiddleware.php        # 日志中间件
├── Events/
│   ├── ConnectionEstablished.php   # 连接建立事件
│   ├── MessageReceived.php         # 消息接收事件
│   ├── ConnectionClosed.php        # 连接关闭事件
│   └── ErrorOccurred.php           # 错误发生事件
└── Contracts/
    ├── TransportInterface.php      # 传输接口
    ├── ResourceInterface.php       # 资源接口
    ├── ToolInterface.php           # 工具接口
    └── MiddlewareInterface.php     # 中间件接口
```

## 核心组件

### 1. MCP服务器（基于php-mcp/laravel）

```php
<?php

namespace App\Modules\Mcp\Server;

use PhpMcp\Laravel\McpServer as BaseMcpServer;
use PhpMcp\Laravel\Contracts\ResourceInterface;
use PhpMcp\Laravel\Contracts\ToolInterface;

class McpServer extends BaseMcpServer
{
    public function __construct(
        private AgentService $agentService,
        private ValidationService $validationService
    ) {
        parent::__construct();
    }

    /**
     * 注册MCP资源
     */
    protected function registerResources(): void
    {
        $this->addResource('task', app(TaskResource::class));
        $this->addResource('project', app(ProjectResource::class));
        $this->addResource('github', app(GitHubResource::class));
    }

    /**
     * 注册MCP工具
     */
    protected function registerTools(): void
    {
        $this->addTool('task_management', app(TaskManagementTool::class));
        $this->addTool('project_query', app(ProjectQueryTool::class));
        $this->addTool('github_sync', app(GitHubSyncTool::class));
    }

    /**
     * 处理Agent认证
     */
    protected function authenticateAgent(string $token): ?Agent
    {
        return $this->agentService->validateToken($token);
    }

    /**
     * 验证MCP消息
     */
    protected function validateMessage(array $message): array
    {
        return $this->validationService->validateMcpMessage($message);
    }

    /**
     * 处理MCP请求
     */
    public function handleRequest(array $request, ?Agent $agent = null): array
    {
        $validatedRequest = $this->validateMessage($request);

        return match($validatedRequest['method']) {
            'initialize' => $this->handleInitialize($validatedRequest['params']),
            'resources/list' => $this->handleResourcesList(),
            'resources/read' => $this->handleResourceRead($validatedRequest['params'], $agent),
            'tools/list' => $this->handleToolsList(),
            'tools/call' => $this->handleToolCall($validatedRequest['params'], $agent),
            default => throw new UnsupportedMethodException($validatedRequest['method'])
        };
    }
}
```

### 2. SSE传输层

```php
<?php

namespace App\Modules\Mcp\Transports;

use App\Modules\Mcp\Contracts\TransportInterface;

class SseTransport implements TransportInterface
{
    /**
     * 启动SSE服务器
     */
    public function start(string $host, int $port): void;

    /**
     * 发送SSE消息
     */
    public function send(Connection $connection, Message $message): void;

    /**
     * 处理SSE连接
     */
    public function handleConnection(Request $request): Connection;

    /**
     * 设置CORS头
     */
    public function setCorsHeaders(Response $response): void;

    /**
     * 发送心跳
     */
    public function sendHeartbeat(Connection $connection): void;
}
```

### 3. 消息路由器

```php
<?php

namespace App\Modules\Mcp\Server;

class MessageRouter
{
    /**
     * 路由消息到处理器
     */
    public function route(Message $message, Connection $connection): Response;

    /**
     * 注册路由
     */
    public function register(string $method, callable $handler): void;

    /**
     * 处理初始化请求
     */
    public function handleInitialize(InitializeRequest $request): InitializeResponse;

    /**
     * 处理资源请求
     */
    public function handleResourceRequest(ResourceRequest $request): ResourceResponse;

    /**
     * 处理工具调用
     */
    public function handleToolCall(ToolCallRequest $request): ToolCallResponse;
}
```

## MCP资源实现

### 项目资源（基于php-mcp/laravel）

```php
<?php

namespace App\Modules\Mcp\Resources;

use PhpMcp\Laravel\Contracts\ResourceInterface;
use PhpMcp\Laravel\Resources\Resource;

class ProjectResource extends Resource implements ResourceInterface
{
    public function __construct(
        private ProjectService $projectService,
        private ValidationService $validationService
    ) {}

    /**
     * 获取资源URI模式
     */
    public function getUriPattern(): string
    {
        return 'project://';
    }

    /**
     * 读取资源
     */
    public function read(string $uri, array $params = []): array
    {
        $parsed = $this->parseUri($uri);

        return match($parsed['type']) {
            'list' => $this->listProjects($params),
            'single' => $this->getProject($parsed['id'], $params),
            'members' => $this->getProjectMembers($parsed['id'], $params),
            'repositories' => $this->getProjectRepositories($parsed['id'], $params),
            default => throw new InvalidUriException("Unsupported URI: {$uri}")
        };
    }

    /**
     * 列出项目
     */
    private function listProjects(array $params): array
    {
        $agent = $this->getCurrentAgent();
        $projects = $this->projectService->getAgentProjects($agent);

        return [
            'projects' => $projects->map(function ($project) {
                return [
                    'id' => $project->id,
                    'name' => $project->name,
                    'description' => $project->description,
                    'status' => $project->status,
                    'created_at' => $project->created_at->toISOString(),
                ];
            })->toArray(),
        ];
    }

    /**
     * 获取单个项目详情
     */
    private function getProject(int $projectId, array $params): array
    {
        $agent = $this->getCurrentAgent();

        if (!$agent->canAccessProject($projectId)) {
            throw new UnauthorizedException('Agent无权访问此项目');
        }

        $project = Project::with(['members', 'repositories', 'tasks'])
            ->findOrFail($projectId);

        return [
            'id' => $project->id,
            'name' => $project->name,
            'description' => $project->description,
            'status' => $project->status,
            'settings' => $project->settings,
            'members_count' => $project->members->count(),
            'repositories_count' => $project->repositories->count(),
            'tasks_count' => $project->tasks->count(),
            'active_tasks_count' => $project->activeTasks->count(),
            'progress' => $project->getProgressPercentage(),
            'created_at' => $project->created_at->toISOString(),
        ];
    }

    /**
     * 验证访问权限
     */
    public function checkAccess(string $agentId, string $uri): bool
    {
        $agent = Agent::findOrFail($agentId);
        $parsed = $this->parseUri($uri);

        if (isset($parsed['id'])) {
            return $agent->canAccessProject($parsed['id']);
        }

        return true; // 列表访问总是允许的
    }
}
```

### 任务资源

```php
<?php

namespace App\Modules\Mcp\Resources;

class TaskResource implements ResourceInterface
{
    /**
     * 支持的URI模式
     * - task://list
     * - task://{id}
     * - task://assigned/{agent_id}
     * - task://status/{status}
     */
    public function getUriPattern(): string
    {
        return 'task://';
    }

    public function read(string $uri, array $params = []): array
    {
        $parsed = $this->parseUri($uri);

        return match($parsed['type']) {
            'list' => $this->listTasks($params),
            'single' => $this->getTask($parsed['id'], $params),
            'assigned' => $this->getAssignedTasks($parsed['agent_id'], $params),
            'status' => $this->getTasksByStatus($parsed['status'], $params),
            default => throw new InvalidUriException("Unsupported URI: {$uri}")
        };
    }
}
```

## MCP工具实现

### 任务管理工具

```php
<?php

namespace App\Modules\Mcp\Tools;

use App\Modules\Mcp\Contracts\ToolInterface;
use App\Modules\Task\Services\TaskService;
use App\Modules\Agent\Services\AuthorizationService;

class TaskTool implements ToolInterface
{
    public function __construct(
        private TaskService $taskService,
        private AuthorizationService $authService
    ) {}

    /**
     * 获取工具名称
     */
    public function getName(): string
    {
        return 'task_management';
    }

    /**
     * 获取工具描述
     */
    public function getDescription(): string
    {
        return 'Manage tasks including creation, updates, and status changes';
    }

    /**
     * 获取工具参数模式
     */
    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'action' => [
                    'type' => 'string',
                    'enum' => ['create', 'update', 'claim', 'complete', 'cancel']
                ],
                'task_id' => ['type' => 'integer'],
                'title' => ['type' => 'string'],
                'description' => ['type' => 'string'],
                'priority' => [
                    'type' => 'string',
                    'enum' => ['low', 'medium', 'high', 'urgent']
                ],
                'project_id' => ['type' => 'integer']
            ],
            'required' => ['action']
        ];
    }

    /**
     * 执行工具 - 通过业务模块处理，MCP只负责协议层
     */
    public function execute(array $arguments, string $agentId): array
    {
        // 1. 验证Agent权限（委托给Agent模块）
        $agent = $this->authService->getAgent($agentId);
        if (!$agent) {
            throw new McpException('Agent not found', 404);
        }

        // 2. 委托给Task模块处理具体业务逻辑
        return match($arguments['action']) {
            'create' => $this->taskService->createForAgent($agent, $arguments),
            'update' => $this->taskService->updateForAgent($agent, $arguments),
            'claim' => $this->taskService->claimForAgent($agent, $arguments['task_id']),
            'complete' => $this->taskService->completeForAgent($agent, $arguments),
            'cancel' => $this->taskService->cancelForAgent($agent, $arguments['task_id']),
            default => throw new McpException('Invalid action', 400)
        };
    }
}
```

## 协议消息格式

### 初始化消息

```json
{
  "jsonrpc": "2.0",
  "method": "initialize",
  "params": {
    "protocolVersion": "1.0",
    "capabilities": {
      "resources": {},
      "tools": {},
      "notifications": {}
    },
    "clientInfo": {
      "name": "mcp-tools-server",
      "version": "1.0.0"
    }
  },
  "id": 1
}
```

### 资源读取消息

```json
{
  "jsonrpc": "2.0",
  "method": "resources/read",
  "params": {
    "uri": "project://123"
  },
  "id": 2
}
```

### 工具调用消息

```json
{
  "jsonrpc": "2.0",
  "method": "tools/call",
  "params": {
    "name": "task_management",
    "arguments": {
      "action": "create",
      "title": "Fix authentication bug",
      "description": "Users cannot login with GitHub OAuth",
      "priority": "high",
      "project_id": 1
    }
  },
  "id": 3
}
```

## 中间件系统

### 认证中间件

```php
<?php

namespace App\Modules\Mcp\Middleware;

class AuthenticationMiddleware implements MiddlewareInterface
{
    public function handle(Message $message, Connection $connection, \Closure $next)
    {
        // 验证访问令牌
        $token = $connection->getHeader('Authorization');
        if (!$this->validateToken($token)) {
            throw new AuthenticationException('Invalid access token');
        }

        // 设置Agent上下文
        $agent = $this->getAgentByToken($token);
        $connection->setAgent($agent);

        return $next($message, $connection);
    }
}
```

### 授权中间件

```php
<?php

namespace App\Modules\Mcp\Middleware;

class AuthorizationMiddleware implements MiddlewareInterface
{
    public function handle(Message $message, Connection $connection, \Closure $next)
    {
        $agent = $connection->getAgent();
        $resource = $message->getResource();

        // 检查资源访问权限
        if (!$this->checkResourceAccess($agent, $resource)) {
            throw new AuthorizationException('Access denied to resource');
        }

        // 检查操作权限
        if (!$this->checkActionPermission($agent, $message->getMethod())) {
            throw new AuthorizationException('Action not permitted');
        }

        return $next($message, $connection);
    }
}
```

## 事件系统

### 连接事件

```php
<?php

namespace App\Modules\Mcp\Events;

class ConnectionEstablished
{
    public function __construct(
        public readonly Connection $connection,
        public readonly string $agentId,
        public readonly array $capabilities,
        public readonly \DateTime $timestamp
    ) {}
}

class MessageReceived
{
    public function __construct(
        public readonly Message $message,
        public readonly Connection $connection,
        public readonly \DateTime $timestamp
    ) {}
}
```

## 配置管理

```php
// config/mcp.php
return [
    'server' => [
        'transport' => env('MCP_TRANSPORT', 'sse'),
        'host' => env('MCP_HOST', 'localhost'),
        'port' => env('MCP_PORT', 8000),
        'timeout' => env('MCP_TIMEOUT', 300),
    ],

    'sse' => [
        'heartbeat_interval' => env('MCP_SSE_HEARTBEAT', 30),
        'max_connections' => env('MCP_SSE_MAX_CONNECTIONS', 1000),
        'cors_enabled' => env('MCP_SSE_CORS', true),
    ],

    'capabilities' => [
        'resources' => true,
        'tools' => true,
        'notifications' => true,
        'prompts' => false,
    ],

    'middleware' => [
        'authentication' => true,
        'authorization' => true,
        'rate_limiting' => true,
        'logging' => true,
    ],
];
```

## 性能优化

### 1. 连接池管理
- 连接复用机制
- 连接超时清理
- 内存使用优化

### 2. 消息处理
- 异步消息处理
- 消息批量处理
- 消息压缩传输

### 3. 缓存策略
- 资源数据缓存
- 权限检查缓存
- 连接状态缓存

---

**相关文档**：
- [Agent代理模块](./agent.md)
- [通知模块](./notification.md)
- [MCP协议规范](../mcp.md)
