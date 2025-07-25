# MCP 工具和资源命名规范

**版本**: 1.0  
**创建时间**: 2025年07月25日  
**适用范围**: MCP Tools 项目所有 MCP 工具和资源  

## 概述

本文档定义了 MCP Tools 项目中所有 MCP 工具（Tools）和资源（Resources）的统一命名规范，旨在提高代码的可维护性、一致性和可读性。

## 设计原则

1. **模块化**: 同模块的工具和资源采用统一前缀
2. **一致性**: 所有命名遵循相同的格式规则
3. **可读性**: 命名清晰表达功能和归属
4. **可扩展性**: 支持新模块的添加和扩展

## 模块定义

### 核心模块

| 模块名 | 前缀 | 描述 | 示例 |
|--------|------|------|------|
| Task | `task_` | 任务管理相关功能 | `task_create_main` |
| Database | `db_` | 数据库操作相关功能 | `db_execute_sql` |
| Question | `question_` | 问题交互相关功能 | `question_ask` |
| Agent | `agent_` | Agent信息相关功能 | `agent_get_info` |
| Time | `time_` | 时间服务相关功能 | `time_get_current` |

### 扩展模块

新增模块应遵循以下规则：
- 前缀使用模块名的简短形式 + 下划线
- 前缀长度建议 2-8 个字符
- 避免与现有前缀冲突

## 工具命名规范

### 格式规则

```
{module_prefix}{action}
```

- **全小写字母**
- **下划线分隔**
- **动词在后**，描述具体操作
- **名词在前**，描述操作对象（如有必要）

### 常用动作词汇

| 动作 | 英文 | 示例 |
|------|------|------|
| 创建 | create | `task_create_main` |
| 获取 | get | `task_get` |
| 列表 | list | `task_list` |
| 更新 | update | `task_update` |
| 删除 | delete | `task_delete` |
| 完成 | complete | `task_complete` |
| 执行 | execute | `db_execute_sql` |
| 测试 | test | `db_test_connection` |
| 添加 | add | `task_add_comment` |

### 工具命名示例

```php
// Task 模块
#[McpTool(name: 'task_create_main', description: '创建主任务')]
#[McpTool(name: 'task_create_sub', description: '创建子任务')]
#[McpTool(name: 'task_list', description: '获取任务列表')]
#[McpTool(name: 'task_get', description: '获取任务详情')]
#[McpTool(name: 'task_complete', description: '完成任务')]
#[McpTool(name: 'task_add_comment', description: '添加评论')]
#[McpTool(name: 'task_get_assigned', description: '获取分配的任务')]

// Database 模块  
#[McpTool(name: 'db_execute_sql', description: '执行SQL查询')]
#[McpTool(name: 'db_list_connections', description: '获取数据库连接列表')]
#[McpTool(name: 'db_test_connection', description: '测试数据库连接')]

// Question 模块
#[McpTool(name: 'question_ask', description: 'Agent向用户提出问题')]

// Agent 模块
#[McpTool(name: 'agent_get_info', description: '获取Agent信息')]

// Time 模块
#[McpTool(name: 'time_get_current', description: '获取当前时间')]
```

## 资源命名规范

### 格式规则

```
{module_prefix}{resource_type}
```

- **全小写字母**
- **下划线分隔**
- **名词描述**资源类型

### URI 模式规范

```
{module}://{path}
```

- **模块名**作为 scheme
- **路径**描述具体资源
- **参数**使用花括号包围：`{id}`、`{agent_id}`

### 资源命名示例

#### 静态资源（McpResource）
用于固定URI的资源：

```php
// Agent 模块
#[McpResource(uri: 'agent://info', name: 'agent_info')]

// Time 模块
#[McpResource(uri: 'time://current', name: 'time_current')]

// Database 模块
#[McpResource(uri: 'db://connections', name: 'db_connection_list')]
```

#### 动态资源模板（McpResourceTemplate）
用于带参数的资源，遵循RFC 6570 URI模板标准：

```php
// Database 模块
#[McpResourceTemplate(
    uriTemplate: 'db://connection/{id}',
    name: 'db_connection',
    description: '获取数据库连接详细信息'
)]
#[McpResourceTemplate(
    uriTemplate: 'db://log/{agentId}',
    name: 'db_execution_log',
    description: '获取Agent的SQL执行日志'
)]
#[McpResourceTemplate(
    uriTemplate: 'db://stats/{agentId}',
    name: 'db_execution_stats',
    description: '获取SQL执行统计信息'
)]
```

## 重构映射表

### 工具重构映射

| 当前名称 | 新名称 | 模块 |
|----------|--------|------|
| `create_main_task` | `task_create_main` | Task |
| `create_sub_task` | `task_create_sub` | Task |
| `list_tasks` | `task_list` | Task |
| `get_task` | `task_get` | Task |
| `complete_task` | `task_complete` | Task |
| `add_comment` | `task_add_comment` | Task |
| `get_assigned_tasks` | `task_get_assigned` | Task |
| `ask_question` | `question_ask` | Question |
| `execute_sql` | `db_execute_sql` | Database |
| `list_connections` | `db_list_connections` | Database |
| `test_connection` | `db_test_connection` | Database |

### 资源重构映射

| 当前名称 | 当前URI | 新名称 | 新URI |
|----------|---------|--------|-------|
| `getTime2` | `time://get2` | `time_current` | `time://current` |
| `myInfo` | `myinfo://get` | `agent_info` | `agent://info` |
| `sqlExecutionLog` | `sqllog://{agentId}` | `db_execution_log` | `db://log/{agent_id}` |
| `sqlExecutionStats` | `sqllog://stats/{agentId}` | `db_execution_stats` | `db://stats/{agent_id}` |
| `databaseConnection` | `dbconnection://{id}` | `db_connection` | `db://connection/{id}` |
| `databaseConnectionList` | `dbconnection://list` | `db_connection_list` | `db://connections` |

## 实施指南

### 重构步骤

1. **创建命名规范文档**（本文档）
2. **重构 Database 模块**（优先级最高）
3. **重构 Task 模块**（影响范围最大）
4. **重构其他模块**（Question、Agent、Time）
5. **更新 MCP 发现缓存**
6. **测试所有功能**
7. **更新相关文档**

### 文件修改范围

- `app/Modules/Mcp/Tools/*.php`
- `app/Modules/Mcp/Resources/*.php`
- `app/Modules/Dbcont/Tools/*.php`
- `app/Modules/Dbcont/Resources/*.php`
- 相关文档和配置文件

### 测试验证

重构完成后需要验证：
1. 所有工具能正常发现和注册
2. 所有资源能正常访问
3. MCP 客户端能正常调用
4. 功能逻辑保持不变

## 维护说明

1. **新增工具/资源**时必须遵循本规范
2. **定期审查**命名一致性
3. **更新文档**当规范发生变化时
4. **版本控制**重要的命名变更

---

**注意**: 本规范的实施可能会影响现有的 MCP 客户端调用，请在重构前做好充分的测试和备份。
