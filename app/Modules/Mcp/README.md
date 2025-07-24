# MCP 协议模块

## 概述

MCP协议模块是MCP Tools的核心通信层，负责实现Model Context Protocol (MCP) 的业务逻辑。该模块专注于为AI Agent提供任务处理和资源访问的标准化接口，不涉及用户管理功能。

**技术架构**：
- **底层能力**：由 `php-mcp/laravel` 包提供 MCP 协议的底层实现
- **业务逻辑**：MCP 模块仅负责具体的 MCP 逻辑实现和业务功能

## 职责范围

### 1. MCP 业务逻辑实现

- 基于 `php-mcp/laravel` 的 Resources 和 Tools 定义
- Agent 身份验证和权限控制逻辑
- 业务数据的 MCP 格式转换
- 特定业务场景的 MCP 操作封装

### 2. 底层协议支持（由 php-mcp/laravel 提供）
- JSON-RPC 2.0 消息格式处理
- 协议版本协商机制
- 能力声明和发现
- SSE (Server-Sent Events) 传输实现
- 连接管理和心跳检测
- 消息路由和错误处理

## 职责边界

### ✅ MCP模块负责
- 定义和实现业务相关的 Resources 和 Tools
- Agent 身份验证和权限控制的业务逻辑
- 业务数据与 MCP 协议格式的转换
- 特定业务场景的 MCP 操作封装

### ❌ MCP模块不负责
- MCP 协议底层实现（由 php-mcp/laravel 处理）
- 传输层和连接管理（由 php-mcp/laravel 处理）
- 用户账户管理和认证
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
├── Services/
│   ├── McpService.php             # MCP业务服务
│   ├── ErrorHandlerService.php    # 错误处理服务
│   └── SessionService.php         # 会话管理服务
├── Resources/
│   ├── ProjectResource.php        # 项目资源
│   ├── TaskResource.php           # 任务资源
│   └── MyInfoResource.php         # 用户信息资源
├── Tools/
│   ├── ProjectTool.php            # 项目工具
│   ├── TaskTool.php               # 任务工具
│   ├── AgentTool.php              # Agent工具
│   └── AskQuestionTool.php        # 问答工具
├── Middleware/
│   └── McpAuthMiddleware.php       # MCP认证中间件
└── Providers/
    └── McpServiceProvider.php     # 服务提供者
```

**注意**：
- 不包含 Controllers 目录，因为 MCP 协议处理由 php-mcp/laravel 包自动完成
- 不包含 routes 目录，路由由 php-mcp/laravel 包自动管理
- 不包含 config 目录，使用项目根目录的 config/mcp.php

## 核心组件

### 1. MCP业务逻辑层（基于php-mcp/laravel）

**主要功能**：
- 利用 php-mcp/laravel 包提供的底层 MCP 协议能力
- 实现业务相关的 Resources 和 Tools
- 集成 Agent 认证和权限验证的业务逻辑
- 提供业务数据的 MCP 格式转换
- 封装特定业务场景的 MCP 操作

**核心职责**：
- 业务 Resources 和 Tools 的定义和实现
- Agent 身份验证和授权的业务逻辑
- 业务数据访问控制
- 业务工具调用管理
- 业务级错误处理和响应

### 2. MCP协议底层（由php-mcp/laravel提供）

**主要功能**：
- 实现 Server-Sent Events 传输协议
- 支持实时双向通信
- 处理连接管理和心跳机制
- 提供 CORS 支持和安全控制
- 支持连接状态监控

**核心特性**：
- JSON-RPC 2.0 消息格式处理
- 协议版本协商和能力声明
- 长连接管理和自动重连机制
- 消息队列和缓冲
- 连接池管理和性能监控

### 3. 业务逻辑路由器

**主要功能**：
- 基于 php-mcp/laravel 的路由能力实现业务逻辑
- 注册业务相关的 Resources 和 Tools
- 提供业务特定的 MCP 方法处理
- 集成业务中间件管道
- 业务级错误处理和响应格式化

**支持的业务路由**：
- 项目资源访问路由
- 任务管理工具路由
- Agent 认证和授权路由
- GitHub 集成路由
- 自定义业务扩展路由

## MCP资源实现

### 项目资源（基于php-mcp/laravel）

**资源URI模式**：`project://`

**支持的URI格式**：
- `project://list` - 获取项目列表
- `project://{id}` - 获取单个项目详情
- `project://{id}/members` - 获取项目成员
- `project://{id}/repositories` - 获取项目仓库

**主要功能**：
- 基于Agent权限的项目访问控制
- 项目基本信息和统计数据提供
- 项目成员和仓库信息查询
- 支持分页和筛选参数

**权限控制**：
- Agent只能访问被授权的项目
- 支持细粒度的资源访问控制
- 提供权限验证和错误处理

### 任务资源

**资源URI模式**：`task://`

**支持的URI格式**：
- `task://list` - 获取任务列表
- `task://{id}` - 获取单个任务详情
- `task://assigned/{agent_id}` - 获取分配给特定Agent的任务
- `task://status/{status}` - 按状态筛选任务

**主要功能**：
- 任务信息的结构化访问
- 支持多种查询和筛选方式
- 提供任务进度和状态信息
- 集成子任务和依赖关系数据

## MCP工具实现

### 任务管理工具

**工具名称**：`task_management`

**工具描述**：提供完整的任务管理功能，包括创建、更新、认领、完成和取消任务

**支持的操作**：
- `create` - 创建新任务
- `update` - 更新任务信息
- `claim` - 认领任务
- `complete` - 完成任务
- `cancel` - 取消任务

**参数模式**：
- `action` (必需) - 要执行的操作类型
- `task_id` - 任务ID（更新、认领、完成、取消操作需要）
- `title` - 任务标题（创建、更新操作需要）
- `description` - 任务描述
- `priority` - 任务优先级（low/medium/high/urgent）
- `project_id` - 项目ID（创建操作需要）

**权限控制**：
- 通过Agent模块验证Agent身份和权限
- 委托给Task模块处理具体业务逻辑
- 支持细粒度的操作权限控制

## 协议消息格式

### 初始化消息

**消息类型**：`initialize`

**主要参数**：
- `protocolVersion` - MCP协议版本
- `capabilities` - 客户端支持的能力
- `clientInfo` - 客户端信息（名称、版本）

**响应内容**：
- 服务器能力声明
- 支持的资源和工具列表
- 协议版本确认

### 资源读取消息

**消息类型**：`resources/read`

**主要参数**：
- `uri` - 资源URI（如：project://123）
- 可选的查询参数和筛选条件

**响应内容**：
- 资源数据内容
- 资源元数据
- 访问权限信息

### 工具调用消息

**消息类型**：`tools/call`

**主要参数**：
- `name` - 工具名称
- `arguments` - 工具参数对象

**响应内容**：
- 工具执行结果
- 操作状态信息
- 错误信息（如有）

## 中间件系统

### 认证中间件

**主要功能**：
- 验证Agent访问令牌
- 设置Agent上下文信息
- 处理认证失败情况
- 支持多种认证方式

**处理流程**：
- 从连接头部提取访问令牌
- 验证令牌有效性和权限
- 设置Agent上下文到连接中
- 传递给下一个中间件

### 授权中间件

**主要功能**：
- 检查Agent资源访问权限
- 验证操作权限
- 实现细粒度权限控制
- 记录权限检查日志

**权限检查**：
- 资源级别的访问控制
- 操作级别的权限验证
- 基于Agent配置的权限矩阵
- 支持动态权限更新

## 事件系统

### 连接事件

**ConnectionEstablished - 连接建立事件**：
- 连接对象信息
- Agent ID标识
- 客户端能力声明
- 连接建立时间戳

**MessageReceived - 消息接收事件**：
- 接收到的消息对象
- 消息来源连接
- 消息接收时间戳
- 消息处理状态

**其他事件**：
- 连接断开事件
- 消息发送事件
- 错误处理事件
- 性能监控事件

## 配置管理

### 服务器配置
- **传输协议**：SSE、WebSocket等传输方式
- **网络设置**：主机地址、端口、超时时间
- **性能参数**：连接池大小、并发限制

### SSE配置
- **心跳间隔**：保持连接活跃的心跳频率
- **连接限制**：最大并发连接数
- **CORS设置**：跨域资源共享配置

### 能力配置
- **资源支持**：是否提供资源访问
- **工具支持**：是否提供工具调用
- **通知支持**：是否支持服务器推送通知
- **提示支持**：是否支持交互式提示

### 中间件配置
- **认证中间件**：启用Agent身份验证
- **授权中间件**：启用权限检查
- **限流中间件**：启用请求频率限制
- **日志中间件**：启用请求日志记录

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
- [MCP协议规范](../MCP协议概述.md)
