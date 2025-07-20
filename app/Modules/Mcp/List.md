# MCP Tools 项目 - MCP 内容清单

**更新时间**: 2025年07月21日
**版本**: 1.0.0
**基于**: php-mcp/laravel 包

## 概述

本文档详细列出了 MCP Tools 项目中所有的 MCP（Model Context Protocol）相关内容，包括工具、资源、配置和服务。

## 当前状态

### 发现统计
- **工具 (Tools)**: 8 个
- **资源 (Resources)**: 1 个
- **提示 (Prompts)**: 0 个
- **模板 (Templates)**: 0 个

## 1. MCP 工具 (Tools)

### 1.1 任务管理工具

#### create_main_task
- **文件**: `app/Modules/Mcp/Tools/TaskTool.php`
- **方法**: `createMainTask()`
- **描述**: 创建主任务
- **参数**:
  - `projectId` (string): 项目ID
  - `title` (string): 任务标题
  - `description` (string, 可选): 任务描述
  - `priority` (string, 可选): 优先级 (默认: medium)
- **权限**: 需要 `create_task` 权限和项目访问权限

#### create_sub_task
- **文件**: `app/Modules/Mcp/Tools/TaskTool.php`
- **方法**: `createSubTask()`
- **描述**: 创建子任务
- **参数**:
  - `parentTaskId` (string): 父任务ID
  - `title` (string): 任务标题
  - `description` (string, 可选): 任务描述
  - `priority` (string, 可选): 优先级 (默认: medium)
- **权限**: 需要 `create_task` 权限和父任务访问权限

#### list_tasks
- **文件**: `app/Modules/Mcp/Tools/TaskTool.php`
- **方法**: `listTasks()`
- **描述**: 获取任务列表
- **参数**:
  - `projectId` (string, 可选): 项目ID过滤
  - `status` (string, 可选): 状态过滤
  - `assignedToMe` (bool, 可选): 是否只显示分配给当前Agent的任务
  - `limit` (int, 可选): 返回数量限制 (默认: 20)
- **权限**: 基于Agent的项目访问权限

#### get_task
- **文件**: `app/Modules/Mcp/Tools/TaskTool.php`
- **方法**: `getTask()`
- **描述**: 获取任务详情
- **参数**:
  - `taskId` (string): 任务ID
- **权限**: 需要任务访问权限

#### complete_task
- **文件**: `app/Modules/Mcp/Tools/TaskTool.php`
- **方法**: `completeTask()`
- **描述**: 完成任务
- **参数**:
  - `taskId` (string): 任务ID
  - `comment` (string, 可选): 完成备注
- **权限**: 需要 `complete_task` 权限

#### add_comment
- **文件**: `app/Modules/Mcp/Tools/TaskTool.php`
- **方法**: `addComment()`
- **描述**: 添加评论
- **参数**:
  - `taskId` (string): 任务ID
  - `content` (string): 评论内容
- **权限**: 需要任务访问权限

#### get_assigned_tasks
- **文件**: `app/Modules/Mcp/Tools/TaskTool.php`
- **方法**: `getAssignedTasks()`
- **描述**: 获取分配给当前Agent的任务
- **参数**:
  - `status` (string, 可选): 状态过滤
  - `limit` (int, 可选): 返回数量限制 (默认: 20)
- **权限**: 自动限制为当前Agent的任务

### 1.2 问题管理工具

#### get_questions
- **文件**: `app/Modules/Mcp/Tools/GetQuestionsTool.php`
- **方法**: `getQuestions()`
- **描述**: 获取问题列表，支持多种过滤条件
- **参数**:
  - `status` (string, 可选): 问题状态 (PENDING/ANSWERED/IGNORED)
  - `priority` (string, 可选): 优先级 (URGENT/HIGH/MEDIUM/LOW)
  - `question_type` (string, 可选): 问题类型 (CHOICE/FEEDBACK)
  - `task_id` (int, 可选): 任务ID过滤
  - `project_id` (int, 可选): 项目ID过滤
  - `limit` (int, 可选): 返回数量限制 (默认: 10)
  - `only_mine` (bool, 可选): 是否只返回当前Agent的问题 (默认: true)
  - `include_expired` (bool, 可选): 是否包含已过期的问题 (默认: false)
- **权限**: 基于Agent身份和项目权限

### 1.3 禁用的工具

#### AskQuestionTool (已禁用)
- **文件**: `app/Modules/Mcp/Tools/AskQuestionTool.php.disabled`
- **状态**: 临时禁用，需要重写为属性模式
- **原因**: 使用了不存在的接口 `PhpMcp\Laravel\Contracts\ToolInterface`

#### CheckAnswerTool (已禁用)
- **文件**: `app/Modules/Mcp/Tools/CheckAnswerTool.php.disabled`
- **状态**: 临时禁用，需要重写为属性模式
- **原因**: 使用了不存在的接口 `PhpMcp\Laravel\Contracts\ToolInterface`

## 2. MCP 资源 (Resources)

### 2.1 时间资源

#### time://get2
- **文件**: `app/Modules/Mcp/Tools/Time2Tool.php`
- **方法**: `getTime2()`
- **名称**: getTime2
- **MIME类型**: application/json
- **描述**: 获取当前时间
- **返回**: 包含当前时间戳和格式化时间的JSON对象

### 2.2 资源文件 (未被发现)

#### ProjectResource
- **文件**: `app/Modules/Mcp/Resources/ProjectResource.php`
- **状态**: 存在但未被MCP发现
- **原因**: 可能缺少正确的属性标记

#### TaskResource
- **文件**: `app/Modules/Mcp/Resources/TaskResource.php`
- **状态**: 存在但未被MCP发现
- **原因**: 可能缺少正确的属性标记

## 3. 配置文件

### 3.1 主配置文件
- **文件**: `config/mcp.php`
- **描述**: MCP服务器的主要配置
- **关键配置**:
  - 服务器信息 (名称、版本)
  - 发现目录配置
  - 传输协议配置 (SSE/HTTP)
  - 能力声明 (tools, resources, prompts等)

### 3.2 环境变量
- **文件**: `.env`
- **MCP相关变量**:
  - `MCP_DISCOVERY_DIRECTORIES`: 发现目录列表
  - `MCP_SERVER_NAME`: 服务器名称
  - `MCP_SERVER_VERSION`: 服务器版本

### 3.3 模块配置
- **文件**: `app/Modules/Mcp/config/mcp.php`
- **状态**: 空配置文件

## 4. 路由配置

### 4.1 MCP路由
- **文件**: `routes/mcp.php`
- **内容**: 手动注册的MCP资源
- **当前注册**: Time2Tool资源 (已注释)

### 4.2 API路由
- **文件**: `app/Modules/Mcp/routes/api.php`
- **端点**:
  - `/api/mcp/info` - 服务器信息
  - `/api/mcp/capabilities` - 能力声明
  - `/api/mcp/status` - 服务器状态
  - `/api/mcp/resources/*` - 资源操作
  - `/api/mcp/tools/*` - 工具调用
  - `/api/mcp/test/*` - 测试端点

## 5. 服务类

### 5.1 MCP服务
- **文件**: `app/Modules/Mcp/Services/McpService.php`
- **功能**: MCP服务器启动和管理

### 5.2 错误处理服务
- **文件**: `app/Modules/Mcp/Services/ErrorHandlerService.php`
- **功能**: MCP错误处理和响应格式化

## 6. 控制器

### 6.1 主控制器
- **文件**: `app/Modules/Mcp/Controllers/McpController.php`
- **功能**: MCP服务器信息和状态

### 6.2 资源控制器
- **文件**: `app/Modules/Mcp/Controllers/ResourceController.php`
- **功能**: 资源CRUD操作

### 6.3 工具控制器
- **文件**: `app/Modules/Mcp/Controllers/ToolController.php`
- **功能**: 工具调用处理

### 6.4 测试控制器
- **文件**: `app/Modules/Mcp/Controllers/McpTestController.php`
- **功能**: MCP功能测试

## 7. 中间件

### 7.1 认证中间件
- **文件**: `app/Modules/Mcp/Middleware/McpAuthMiddleware.php`
- **功能**: MCP请求认证

## 8. 服务提供者

### 8.1 MCP服务提供者
- **文件**: `app/Modules/Mcp/Providers/McpServiceProvider.php`
- **功能**: MCP服务注册和启动

## 9. 发现机制

### 9.1 自动发现
- **配置**: `config/mcp.php` 中的 `discovery` 部分
- **扫描目录**:
  - `app/Mcp` (不存在)
  - `app/Modules/Mcp/Tools`
  - `app/Modules/Mcp/Resources` (通过环境变量添加)
- **发现方式**: 基于 `#[McpTool]` 和 `#[McpResource]` 属性

### 9.2 手动注册
- **文件**: `routes/mcp.php`
- **方式**: 使用 `Mcp::resource()` 和 `Mcp::tool()` 方法

## 10. 已知问题

### 10.1 接口依赖问题 (已解决)
- **问题**: 部分工具类引用不存在的接口
- **影响**: 导致MCP发现过程中断
- **解决**: 重写为属性模式或临时禁用

### 10.2 资源发现问题
- **问题**: ProjectResource 和 TaskResource 未被发现
- **可能原因**: 缺少正确的属性标记
- **状态**: 待解决

## 11. 待办事项

### 11.1 高优先级
1. 重写禁用的工具类 (AskQuestionTool, CheckAnswerTool)
2. 修复资源发现问题 (ProjectResource, TaskResource)
3. 添加更多资源和工具

### 11.2 中优先级
1. 完善错误处理和日志记录
2. 添加工具和资源的单元测试
3. 优化性能和缓存机制

### 11.3 低优先级
1. 添加更多MCP功能 (Prompts, Templates)
2. 完善文档和示例
3. 添加监控和指标收集

## 12. 技术架构

### 12.1 MCP 属性模式
项目使用 php-mcp/laravel 包的属性模式：
- `#[McpTool]` - 标记工具方法
- `#[McpResource]` - 标记资源方法
- `#[McpPrompt]` - 标记提示方法 (未使用)
- `#[McpResourceTemplate]` - 标记资源模板 (未使用)

### 12.2 权限控制
- 基于 Agent 身份认证
- 项目级权限控制
- 操作级权限验证
- 通过 AuthenticationService 和 AuthorizationService 实现

### 12.3 错误处理
- 统一的错误响应格式
- 详细的错误日志记录
- 权限拒绝和业务逻辑错误的区分处理

## 13. 使用示例

### 13.1 工具调用示例
```json
{
  "method": "tools/call",
  "params": {
    "name": "create_main_task",
    "arguments": {
      "projectId": "1",
      "title": "新任务",
      "description": "任务描述",
      "priority": "high"
    }
  }
}
```

### 13.2 资源访问示例
```json
{
  "method": "resources/read",
  "params": {
    "uri": "time://get2"
  }
}
```

## 14. 监控和调试

### 14.1 MCP 命令
- `php artisan mcp:list` - 列出所有MCP元素
- `php artisan mcp:discover --force` - 强制重新发现
- `php artisan mcp:list tools` - 只列出工具
- `php artisan mcp:list resources` - 只列出资源

### 14.2 测试端点
- `/api/mcp/test/status` - 测试服务器状态
- `/api/mcp/test/functions` - 测试MCP功能
- `/api/mcp/test/tool/{toolName}` - 测试特定工具
- `/api/mcp/test/resource/{resourceUri}` - 测试特定资源