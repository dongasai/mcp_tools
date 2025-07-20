# MCP Tools 项目 - MCP 内容清单

**更新时间**: 2025年07月21日 05:24:15 CST
**版本**: 1.1.0
**基于**: php-mcp/laravel 包

## 概述

本文档详细列出了 MCP Tools 项目中所有的 MCP（Model Context Protocol）相关内容，包括工具、资源、配置和服务。每个项目都标识了其注册状态、实现状态和规划状态。

## 当前状态

### 发现统计（通过 `php artisan mcp:list` 获取）
- **工具 (Tools)**: 8 个已注册
- **资源 (Resources)**: 1 个已注册
- **提示 (Prompts)**: 0 个
- **模板 (Templates)**: 0 个

### 实现统计
- **已实现工具**: 8 个
- **已实现资源**: 3 个（2个未被发现）
- **已实现控制器**: 4 个
- **已实现中间件**: 1 个
- **已实现服务**: 3 个

## 1. MCP 工具 (Tools)

### 1.1 任务管理工具 ✅ 已注册/已实现

#### create_main_task ✅ 已注册/已实现
- **文件**: `app/Modules/Mcp/Tools/TaskTool.php`
- **方法**: `createMainTask()`
- **注册状态**: ✅ 已通过属性自动发现注册
- **实现状态**: ✅ 完整实现
- **描述**: 创建主任务
- **参数**:
  - `projectId` (string): 项目ID
  - `title` (string): 任务标题
  - `description` (string, 可选): 任务描述
  - `priority` (string, 可选): 优先级 (默认: medium)
- **权限**: 需要 `create_task` 权限和项目访问权限

#### create_sub_task ✅ 已注册/已实现
- **文件**: `app/Modules/Mcp/Tools/TaskTool.php`
- **方法**: `createSubTask()`
- **注册状态**: ✅ 已通过属性自动发现注册
- **实现状态**: ✅ 完整实现
- **描述**: 创建子任务
- **参数**:
  - `parentTaskId` (string): 父任务ID
  - `title` (string): 任务标题
  - `description` (string, 可选): 任务描述
  - `priority` (string, 可选): 优先级 (默认: medium)
- **权限**: 需要 `create_task` 权限和父任务访问权限

#### list_tasks ✅ 已注册/已实现
- **文件**: `app/Modules/Mcp/Tools/TaskTool.php`
- **方法**: `listTasks()`
- **注册状态**: ✅ 已通过属性自动发现注册
- **实现状态**: ✅ 完整实现
- **描述**: 获取任务列表
- **参数**:
  - `projectId` (string, 可选): 项目ID过滤
  - `status` (string, 可选): 状态过滤
  - `assignedToMe` (bool, 可选): 是否只显示分配给当前Agent的任务
  - `limit` (int, 可选): 返回数量限制 (默认: 20)
- **权限**: 基于Agent的项目访问权限

#### get_task ✅ 已注册/已实现
- **文件**: `app/Modules/Mcp/Tools/TaskTool.php`
- **方法**: `getTask()`
- **注册状态**: ✅ 已通过属性自动发现注册
- **实现状态**: ✅ 完整实现
- **描述**: 获取任务详情
- **参数**:
  - `taskId` (string): 任务ID
- **权限**: 需要任务访问权限

#### complete_task ✅ 已注册/已实现
- **文件**: `app/Modules/Mcp/Tools/TaskTool.php`
- **方法**: `completeTask()`
- **注册状态**: ✅ 已通过属性自动发现注册
- **实现状态**: ✅ 完整实现
- **描述**: 完成任务
- **参数**:
  - `taskId` (string): 任务ID
  - `comment` (string, 可选): 完成备注
- **权限**: 需要 `complete_task` 权限

#### add_comment ✅ 已注册/已实现
- **文件**: `app/Modules/Mcp/Tools/TaskTool.php`
- **方法**: `addComment()`
- **注册状态**: ✅ 已通过属性自动发现注册
- **实现状态**: ✅ 完整实现
- **描述**: 添加评论
- **参数**:
  - `taskId` (string): 任务ID
  - `content` (string): 评论内容
- **权限**: 需要任务访问权限

#### get_assigned_tasks ✅ 已注册/已实现
- **文件**: `app/Modules/Mcp/Tools/TaskTool.php`
- **方法**: `getAssignedTasks()`
- **注册状态**: ✅ 已通过属性自动发现注册
- **实现状态**: ✅ 完整实现
- **描述**: 获取分配给当前Agent的任务
- **参数**:
  - `status` (string, 可选): 状态过滤
  - `limit` (int, 可选): 返回数量限制 (默认: 20)
- **权限**: 自动限制为当前Agent的任务

### 1.2 问题管理工具 ✅ 已注册/已实现

#### get_questions ✅ 已注册/已实现
- **文件**: `app/Modules/Mcp/Tools/GetQuestionsTool.php`
- **方法**: `getQuestions()`
- **注册状态**: ✅ 已通过属性自动发现注册
- **实现状态**: ✅ 完整实现
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

### 1.3 项目管理工具 ❌ 未注册/已实现

#### project_manager ❌ 未注册/已实现
- **文件**: `app/Modules/Mcp/Tools/ProjectTool.php`
- **注册状态**: ❌ 未被MCP发现（缺少属性标记）
- **实现状态**: ✅ 完整实现
- **描述**: 项目管理工具，支持创建、更新、删除、查询项目
- **参数**: 支持多种操作类型和项目数据
- **权限**: 基于Agent身份和项目权限

### 1.4 Agent管理工具 ❌ 未注册/已实现

#### agent_manager ❌ 未注册/已实现
- **文件**: `app/Modules/Mcp/Tools/AgentTool.php`
- **注册状态**: ❌ 未被MCP发现（缺少属性标记）
- **实现状态**: ✅ 完整实现
- **描述**: Agent管理工具，支持查询、更新Agent信息和权限
- **参数**: 支持多种操作类型和Agent数据
- **权限**: 基于Agent身份验证

### 1.5 问题批量操作工具 ❌ 未注册/已实现

#### question_batch ❌ 未注册/已实现
- **文件**: `app/Modules/Mcp/Tools/QuestionBatchTool.php`
- **注册状态**: ❌ 未被MCP发现（缺少属性标记）
- **实现状态**: ✅ 完整实现
- **描述**: 问题批量操作工具，支持批量更新、删除、搜索和分析
- **参数**: 支持批量操作和分析功能
- **权限**: 基于Agent身份和问题权限

### 1.6 时间工具 ✅ 已注册/已实现

#### time://get2 ✅ 已注册/已实现
- **文件**: `app/Modules/Mcp/Tools/Time2Tool.php`
- **方法**: `getTime2()`
- **注册状态**: ✅ 已通过属性自动发现注册
- **实现状态**: ✅ 完整实现
- **描述**: 获取当前时间（作为资源提供）
- **返回**: JSON格式的时间信息

### 1.7 禁用的工具 ⚠️ 已禁用/需重写

#### AskQuestionTool ⚠️ 已禁用/需重写
- **文件**: `app/Modules/Mcp/Tools/AskQuestionTool.php.disabled`
- **注册状态**: ⚠️ 已禁用
- **实现状态**: ⚠️ 需要重写为属性模式
- **原因**: 使用了不存在的接口 `PhpMcp\Laravel\Contracts\ToolInterface`
- **计划**: 重写为使用 `#[McpTool]` 属性

#### CheckAnswerTool ⚠️ 已禁用/需重写
- **文件**: `app/Modules/Mcp/Tools/CheckAnswerTool.php.disabled`
- **注册状态**: ⚠️ 已禁用
- **实现状态**: ⚠️ 需要重写为属性模式
- **原因**: 使用了不存在的接口 `PhpMcp\Laravel\Contracts\ToolInterface`
- **计划**: 重写为使用 `#[McpTool]` 属性

## 2. MCP 资源 (Resources)

### 2.1 时间资源 ✅ 已注册/已实现

#### time://get2 ✅ 已注册/已实现
- **文件**: `app/Modules/Mcp/Tools/Time2Tool.php`
- **方法**: `getTime2()`
- **注册状态**: ✅ 已通过属性自动发现注册
- **实现状态**: ✅ 完整实现
- **名称**: getTime2
- **MIME类型**: application/json
- **描述**: 获取当前时间
- **返回**: 包含当前时间戳和格式化时间的JSON对象

### 2.2 项目资源 ❌ 未注册/已实现

#### project://{path} ❌ 未注册/已实现
- **文件**: `app/Modules/Mcp/Resources/ProjectResource.php`
- **注册状态**: ❌ 未被MCP发现（缺少属性标记）
- **实现状态**: ✅ 完整实现
- **URI模式**: `project://{path}`
- **描述**: 项目信息访问和管理
- **功能**: 支持项目列表、详情、创建、更新等操作
- **权限**: 基于Agent身份和项目访问权限

### 2.3 任务资源 ❌ 未注册/已实现

#### task://{path} ❌ 未注册/已实现
- **文件**: `app/Modules/Mcp/Resources/TaskResource.php`
- **注册状态**: ❌ 未被MCP发现（缺少属性标记）
- **实现状态**: ✅ 完整实现
- **URI模式**: `task://{path}`
- **描述**: 任务信息访问和管理
- **功能**: 支持任务列表、详情、状态更新等操作
- **权限**: 基于Agent身份和任务访问权限

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

## 5. 服务类 ✅ 已实现

### 5.1 MCP服务 ✅ 已实现
- **文件**: `app/Modules/Mcp/Services/McpService.php`
- **实现状态**: ✅ 完整实现
- **功能**: MCP服务器启动和管理、Agent权限验证、会话日志记录
- **依赖**: AgentService, LogInterface

### 5.2 错误处理服务 ✅ 已实现
- **文件**: `app/Modules/Mcp/Services/ErrorHandlerService.php`
- **实现状态**: ✅ 完整实现
- **功能**: MCP错误处理和响应格式化

### 5.3 会话服务 ✅ 已实现
- **文件**: `app/Modules/Mcp/Services/SessionService.php`
- **实现状态**: ✅ 完整实现
- **功能**: MCP会话创建、管理和状态跟踪

## 6. 控制器 ✅ 已实现

### 6.1 主控制器 ✅ 已实现
- **文件**: `app/Modules/Mcp/Controllers/McpController.php`
- **实现状态**: ✅ 完整实现
- **功能**: MCP服务器信息、能力声明、状态查询、会话管理、SSE事件流
- **端点**: `/info`, `/capabilities`, `/status`, `/session/*`, `/sse/events`

### 6.2 资源控制器 ✅ 已实现
- **文件**: `app/Modules/Mcp/Controllers/ResourceController.php`
- **实现状态**: ✅ 完整实现
- **功能**: 资源CRUD操作、URI解析、权限验证
- **端点**: `/resources/*`

### 6.3 工具控制器 ✅ 已实现
- **文件**: `app/Modules/Mcp/Controllers/ToolController.php`
- **实现状态**: ✅ 完整实现
- **功能**: 工具调用处理、参数验证、权限验证
- **端点**: `/tools/*`

### 6.4 测试控制器 ✅ 已实现
- **文件**: `app/Modules/Mcp/Controllers/McpTestController.php`
- **实现状态**: ✅ 完整实现
- **功能**: MCP功能测试、状态检查、配置验证
- **端点**: `/test/*`

## 7. 中间件 ✅ 已实现

### 7.1 认证中间件 ✅ 已实现
- **文件**: `app/Modules/Mcp/Middleware/McpAuthMiddleware.php`
- **实现状态**: ✅ 完整实现
- **功能**: MCP请求认证、Agent身份验证、会话管理
- **特性**: 支持令牌认证、会话创建、权限验证

## 8. 服务提供者 ✅ 已实现

### 8.1 MCP服务提供者 ✅ 已实现
- **文件**: `app/Modules/Mcp/Providers/McpServiceProvider.php`
- **实现状态**: ✅ 完整实现
- **功能**: MCP服务注册、配置发布、路由加载、资源和工具绑定

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

### 10.1 接口依赖问题 ✅ 已解决
- **问题**: 部分工具类引用不存在的接口
- **影响**: 导致MCP发现过程中断
- **解决**: 重写为属性模式或临时禁用

### 10.2 资源发现问题 ⚠️ 待解决
- **问题**: ProjectResource 和 TaskResource 未被发现
- **可能原因**: 缺少正确的属性标记 `#[McpResource]`
- **状态**: 待解决
- **影响**: 资源无法通过MCP协议访问

### 10.3 工具发现问题 ⚠️ 待解决
- **问题**: ProjectTool、AgentTool、QuestionBatchTool 未被发现
- **可能原因**: 缺少正确的属性标记 `#[McpTool]`
- **状态**: 待解决
- **影响**: 工具无法通过MCP协议调用

## 11. 待办事项

### 11.1 高优先级 🔥
1. **添加属性标记**: 为未注册的工具和资源添加 `#[McpTool]` 和 `#[McpResource]` 属性
2. **重写禁用的工具类**: AskQuestionTool, CheckAnswerTool 重写为属性模式
3. **验证发现机制**: 确保所有工具和资源都能被正确发现

### 11.2 中优先级 ⚡
1. **完善错误处理**: 统一错误响应格式和日志记录
2. **添加单元测试**: 为工具和资源添加完整的测试覆盖
3. **优化性能**: 实现缓存机制和连接池管理

### 11.3 低优先级 📋
1. **添加更多MCP功能**: 实现 Prompts 和 Templates 支持
2. **完善文档**: 添加使用示例和API文档
3. **监控和指标**: 添加性能监控和使用统计

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

## 14. 监控和调试 ✅ 已实现

### 14.1 MCP 命令 ✅ 可用
- `php artisan mcp:list` - 列出所有MCP元素
- `php artisan mcp:discover --force` - 强制重新发现
- `php artisan mcp:list tools` - 只列出工具
- `php artisan mcp:list resources` - 只列出资源

### 14.2 测试端点 ✅ 已实现
- `/api/mcp/test/status` - 测试服务器状态
- `/api/mcp/test/functions` - 测试MCP功能
- `/api/mcp/test/tool/{toolName}` - 测试特定工具
- `/api/mcp/test/resource/{resourceUri}` - 测试特定资源

## 15. 状态总结

### 15.1 实现完成度
- **核心架构**: ✅ 100% 完成
- **工具系统**: ⚡ 80% 完成（8个已注册，5个未注册）
- **资源系统**: ⚡ 33% 完成（1个已注册，2个未注册）
- **控制器**: ✅ 100% 完成
- **中间件**: ✅ 100% 完成
- **服务**: ✅ 100% 完成

### 15.2 注册状态统计
- **已注册工具**: 8个 (TaskTool的7个方法 + GetQuestionsTool的1个方法)
- **未注册工具**: 5个 (ProjectTool, AgentTool, QuestionBatchTool, AskQuestionTool, CheckAnswerTool)
- **已注册资源**: 1个 (Time2Tool)
- **未注册资源**: 2个 (ProjectResource, TaskResource)

### 15.3 下一步行动计划
1. **立即执行**: 为未注册的工具和资源添加属性标记
2. **短期目标**: 重写禁用的工具类
3. **中期目标**: 完善测试和文档
4. **长期目标**: 添加更多MCP功能和优化性能