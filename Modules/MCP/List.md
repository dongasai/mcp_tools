# MCP Tools 项目 - 'MCP内容'清单

**更新时间**: 2025年07月26日 03:30:00 CST
**版本**: 2.0.1
**基于**: php-mcp/laravel 包

## 概述

本文档详细列出了 MCP Tools 项目中所有的 MCP（Model Context Protocol）的内容，包括工具、资源、提示、模板。每个条目都标识了其注册状态、实现状态和规划状态。

> 注意MCP内容只有‘工具/资源/提示/模板‘四类，其他的都不是MCP内容

## 当前状态

### 发现统计（通过 `php artisan mcp:list` 获取）
- **工具 (Tools)**: 11 个已注册
- **资源 (Resources)**: 6 个已注册
- **提示 (Prompts)**: 0 个
- **模板 (Templates)**: 0 个

### 实现统计
- **已实现工具**: 11 个（全部已注册）
- **已实现资源**: 6 个（全部已注册）

## 列表

### MCP工具 (11个已注册)

#### 任务管理工具 (7个)
1. **task_create_main** - 创建主任务 ✅
2. **task_create_sub** - 创建子任务 ✅
3. **task_list** - 获取任务列表 ✅
4. **task_get** - 获取任务详情 ✅
5. **task_complete** - 完成任务 ✅
6. **task_add_comment** - 添加评论 ✅
7. **task_get_assigned** - 获取分配给当前Agent的任务 ✅

#### 交互工具 (1个)
8. **question_ask** - Agent向用户提问（阻塞式等待回答） ✅

#### 数据库工具 (3个)
9. **db_execute_sql** - 执行SQL查询 ✅
10. **db_list_connections** - 获取数据库连接列表 ✅
11. **db_test_connection** - 测试数据库连接 ✅

### MCP资源 (6个已注册)

#### 基础资源 (2个)
1. **time://current** - 获取当前时间 ✅
2. **agent://info** - 获取当前Agent和项目信息 ✅

#### 数据库资源 (4个)
3. **db://connection/{id}** - 数据库连接详情 ✅
4. **db://connections** - 数据库连接列表 ✅
5. **db://log/{agentId}** - SQL执行日志 ✅
6. **db://stats/{agentId}** - SQL执行统计 ✅



## 1. MCP 工具 (Tools)


### 1.1 任务管理工具 ✅ 已注册/已实现

#### task_create_main ✅ 已注册/已实现
- **文件**: `app/Modules/MCP/Tools/TaskTool.php`
- **方法**: `createMainTask()`
- **注册状态**: ✅ 已通过属性自动发现注册
- **实现状态**: ✅ 已修正（自动使用Agent绑定的项目）
- **描述**: 创建主任务
- **参数**:
  - `title` (string): 任务标题
  - `description` (string, 可选): 任务描述
  - `priority` (string, 可选): 优先级 (默认: medium)
- **权限**: 需要 `create_task` 权限，自动使用Agent绑定的项目

#### task_create_sub ✅ 已注册/已实现
- **文件**: `app/Modules/MCP/Tools/TaskTool.php`
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

#### task_list ✅ 已注册/已实现
- **文件**: `app/Modules/MCP/Tools/TaskTool.php`
- **方法**: `listTasks()`
- **注册状态**: ✅ 已通过属性自动发现注册
- **实现状态**: ✅ 已修正（自动使用Agent绑定的项目）
- **描述**: 获取任务列表
- **参数**:
  - `status` (string, 可选): 状态过滤
  - `type` (string, 可选): 任务类型过滤
  - `assignedToMe` (bool, 可选): 是否只显示分配给当前Agent的任务
  - `limit` (int, 可选): 返回数量限制 (默认: 20)
- **权限**: 基于Agent的项目访问权限，自动限制为Agent绑定的项目

#### task_get ✅ 已注册/已实现
- **文件**: `app/Modules/MCP/Tools/TaskTool.php`
- **方法**: `getTask()`
- **注册状态**: ✅ 已通过属性自动发现注册
- **实现状态**: ✅ 完整实现
- **描述**: 获取任务详情
- **参数**:
  - `taskId` (string): 任务ID
- **权限**: 需要任务访问权限

#### task_complete ✅ 已注册/已实现
- **文件**: `app/Modules/MCP/Tools/TaskTool.php`
- **方法**: `completeTask()`
- **注册状态**: ✅ 已通过属性自动发现注册
- **实现状态**: ✅ 完整实现
- **描述**: 完成任务
- **参数**:
  - `taskId` (string): 任务ID
  - `comment` (string, 可选): 完成备注
- **权限**: 需要 `complete_task` 权限

#### task_add_comment ✅ 已注册/已实现
- **文件**: `app/Modules/MCP/Tools/TaskTool.php`
- **方法**: `addComment()`
- **注册状态**: ✅ 已通过属性自动发现注册
- **实现状态**: ✅ 完整实现
- **描述**: 添加评论
- **参数**:
  - `taskId` (string): 任务ID
  - `content` (string): 评论内容
- **权限**: 需要任务访问权限

#### task_get_assigned ✅ 已注册/已实现
- **文件**: `app/Modules/MCP/Tools/TaskTool.php`
- **方法**: `getAssignedTasks()`
- **注册状态**: ✅ 已通过属性自动发现注册
- **实现状态**: ✅ 完整实现
- **描述**: 获取分配给当前Agent的任务
- **参数**:
  - `status` (string, 可选): 状态过滤
  - `limit` (int, 可选): 返回数量限制 (默认: 20)
- **权限**: 自动限制为当前Agent的任务

### 1.2 问题管理工具 ✅ 已注册/已实现

#### question_ask ✅ 已注册/已实现
- **文件**: `app/Modules/MCP/Tools/AskQuestionTool.php`
- **方法**: `askQuestion()`
- **注册状态**: ✅ 已通过属性自动发现注册
- **实现状态**: ✅ 完整实现（阻塞式等待回答）
- **描述**: Agent向用户提出问题，等待回答（阻塞式，超时600秒）
- **参数**:
  - `title` (string): 问题标题
  - `content` (string): 问题内容
  - `priority` (string, 可选): 优先级 (URGENT/HIGH/MEDIUM/LOW，默认: MEDIUM)
  - `task_id` (int, 可选): 关联任务ID
  - `context` (array, 可选): 上下文信息
  - `timeout` (int, 可选): 超时时间（秒，默认: 600）
- **权限**: 基于Agent身份和项目权限，自动使用Agent绑定的项目

### 1.3 项目管理工具 ❌ 未注册/已实现 ⚠️ 不合理的，MCP不能进行项目管理

#### project_manager ❌ 未注册/已实现  ⚠️ 不合理的，MCP不能进行项目管理
- **文件**: `app/Modules/MCP/Tools/ProjectTool.php`
- **注册状态**: ❌ 未被MCP发现（缺少属性标记）
- **实现状态**: ✅ 完整实现
- **描述**: 项目管理工具，支持创建、更新、删除、查询项目
- **参数**: 支持多种操作类型和项目数据
- **权限**: 基于Agent身份和项目权限

### 1.4 Agent管理工具 ❌ 未注册/已实现 ⚠️ 不合理的，MCP不能进行Agent管理

#### agent_manager ❌ 未注册/已实现 ⚠️ 不合理的，MCP不能进行Agent管理
- **文件**: `app/Modules/MCP/Tools/AgentTool.php`
- **注册状态**: ❌ 未被MCP发现（缺少属性标记）
- **实现状态**: ✅ 完整实现
- **描述**: Agent管理工具，支持查询、更新Agent信息和权限
- **参数**: 支持多种操作类型和Agent数据
- **权限**: 基于Agent身份验证

### 1.5 问题批量操作工具 ❌ 未注册/已实现 ⚠️ 不合理的，问题对Agent来说就是一问一答，其他的都是垃圾



### 1.6 数据库工具 ✅ 已注册/已实现

#### db_execute_sql ✅ 已注册/已实现
- **文件**: `app/Modules/MCP/Tools/SqlExecutionTool.php`
- **方法**: `executeSql()`
- **注册状态**: ✅ 已通过属性自动发现注册
- **实现状态**: ✅ 完整实现
- **描述**: 执行SQL查询，连接ID可选（默认使用第一个可用连接）
- **参数**:
  - `sql` (string): SQL查询语句
  - `connectionId` (int, 可选): 数据库连接ID，如果为null则自动选择第一个可用连接
  - `timeout` (int, 可选): 查询超时时间（秒）
  - `maxRows` (int, 可选): 最大返回行数
- **权限**: Agent必须有数据库连接访问权限
- **安全**: 包含SQL验证、权限检查、查询限制

#### db_list_connections ✅ 已注册/已实现
- **文件**: `app/Modules/MCP/Tools/SqlExecutionTool.php`
- **方法**: `listConnections()`
- **注册状态**: ✅ 已通过属性自动发现注册
- **实现状态**: ✅ 完整实现
- **描述**: 获取数据库连接列表
- **返回**: Agent有权限访问的数据库连接列表，包含连接信息、权限级别、状态信息
- **权限**: 只返回Agent有权限的连接

#### db_test_connection ✅ 已注册/已实现
- **文件**: `app/Modules/MCP/Tools/SqlExecutionTool.php`
- **方法**: `testConnection()`
- **注册状态**: ✅ 已通过属性自动发现注册
- **实现状态**: ✅ 完整实现
- **描述**: 测试数据库连接
- **参数**:
  - `connectionId` (int, 可选): 数据库连接ID，如果为null则自动选择第一个可用连接
- **返回**: 连接测试结果、延迟信息、错误诊断
- **权限**: Agent必须有数据库连接访问权限

### 1.7 时间工具 ✅ 已注册/已实现

#### time://current ✅ 已注册/已实现
- **文件**: `app/Modules/MCP/Tools/Time2Tool.php`
- **方法**: `getTime2()`
- **注册状态**: ✅ 已通过属性自动发现注册
- **实现状态**: ✅ 完整实现
- **描述**: 获取当前时间（作为资源提供）
- **返回**: JSON格式的时间信息





## 2. MCP 资源 (Resources)

### 2.1 时间资源 ✅ 已注册/已实现

#### time://current ✅ 已注册/已实现
- **文件**: `app/Modules/MCP/Tools/Time2Tool.php`
- **方法**: `getTime2()`
- **注册状态**: ✅ 已通过属性自动发现注册
- **实现状态**: ✅ 完整实现
- **名称**: time_current
- **MIME类型**: application/json
- **描述**: 获取当前时间
- **返回**: 包含当前时间戳和格式化时间的JSON对象

### 2.2 Agent信息资源 ✅ 已注册/已实现

#### agent://info ✅ 已注册/已实现
- **文件**: `app/Modules/MCP/Resources/MyInfoResource.php`
- **方法**: `getMyInfo()`
- **注册状态**: ✅ 已通过属性自动发现注册
- **实现状态**: ✅ 完整实现
- **名称**: agent_info
- **MIME类型**: application/json
- **描述**: 获取当前Agent和项目信息
- **返回**: 包含以下完整信息的JSON对象：
  - **Agent信息**: ID、标识符、名称、状态、描述、能力、配置、权限、活跃时间
  - **用户信息**: ID、姓名、用户名、邮箱
  - **项目信息**: 总数、可访问项目列表、项目详情（名称、描述、状态、仓库URL、设置）
  - **权限信息**: 允许访问的项目ID列表、允许执行的操作权限
  - **统计信息**: 项目总数、可访问项目数、活跃项目数
- **权限**: 基于Agent认证，返回当前Agent的完整信息
- **用途**: 为AI Agent提供自我认知能力，了解自己的身份、权限和可访问的项目

### 2.3 数据库资源 ✅ 已注册/已实现

#### db://connection/{id} ✅ 已注册/已实现
- **文件**: `app/Modules/MCP/Resources/DatabaseConnectionResource.php`
- **方法**: `getDatabaseConnection()`
- **注册状态**: ✅ 已通过属性自动发现注册
- **实现状态**: ✅ 完整实现
- **名称**: db_connection
- **MIME类型**: application/json
- **描述**: 获取数据库连接详细信息
- **URI模式**: `db://connection/{id}`
- **返回**: 包含连接信息、权限详情、统计数据的JSON对象
- **权限**: Agent必须有对应连接的访问权限

#### db://connections ✅ 已注册/已实现
- **文件**: `app/Modules/MCP/Resources/DatabaseConnectionResource.php`
- **方法**: `getDatabaseConnectionList()`
- **注册状态**: ✅ 已通过属性自动发现注册
- **实现状态**: ✅ 完整实现
- **名称**: db_connection_list
- **MIME类型**: application/json
- **描述**: 获取所有可访问的数据库连接列表
- **URI**: `db://connections`
- **返回**: Agent有权限的连接列表，包含连接信息和权限级别
- **权限**: 只返回Agent有权限的连接

#### db://log/{agentId} ✅ 已注册/已实现
- **文件**: `app/Modules/MCP/Resources/SqlExecutionLogResource.php`
- **方法**: `getSqlExecutionLog()`
- **注册状态**: ✅ 已通过属性自动发现注册
- **实现状态**: ✅ 完整实现
- **名称**: db_execution_log
- **MIME类型**: application/json
- **描述**: 获取Agent的SQL执行日志
- **URI模式**: `db://log/{agentId}`
- **返回**: SQL执行历史记录，支持筛选和分页
- **权限**: Agent只能访问自己的执行日志

#### db://stats/{agentId} ✅ 已注册/已实现
- **文件**: `app/Modules/MCP/Resources/SqlExecutionLogResource.php`
- **方法**: `getSqlExecutionStats()`
- **注册状态**: ✅ 已通过属性自动发现注册
- **实现状态**: ✅ 完整实现
- **名称**: db_execution_stats
- **MIME类型**: application/json
- **描述**: 获取SQL执行统计信息
- **URI模式**: `db://stats/{agentId}`
- **返回**: 执行统计、性能分析、使用趋势数据
- **权限**: Agent只能访问自己的统计信息

### 2.4 项目资源 ❌ 未注册/已实现 (已废弃)

#### project://{path} ❌ 未注册/已实现
- **文件**: `app/Modules/MCP/Resources/ProjectResource.php`
- **注册状态**: ❌ 未被MCP发现（缺少属性标记）
- **实现状态**: ✅ 完整实现
- **URI模式**: `project://{path}`
- **描述**: 项目信息访问和管理
- **功能**: 支持项目列表、详情、创建、更新等操作
- **权限**: 基于Agent身份和项目访问权限

### 2.5 任务资源 ❌ 未注册/已实现 (已废弃)

#### task://{path} ❌ 未注册/已实现
- **文件**: `app/Modules/MCP/Resources/TaskResource.php`
- **注册状态**: ❌ 未被MCP发现（缺少属性标记）
- **实现状态**: ✅ 完整实现
- **URI模式**: `task://{path}`
- **描述**: 任务信息访问和管理
- **功能**: 支持任务列表、详情、状态更新等操作
- **权限**: 基于Agent身份和任务访问权限
- **废弃原因**: 任务管理已通过MCP工具实现，资源方式访问不再需要

## 修正记录

### 2025年07月21日 08:30 - Agent项目关系修正 ✅

**修正内容**:
1. **Agent模型权限方法更新** (`app/Modules/Agent/Models/Agent.php`):
   - 修正 `hasProjectAccess()` 方法使用 `project_id` 字段
   - 更新 `setProjectAccess()` 和 `removeProjectAccess()` 方法
   - 修正 `scopeWithProjectAccess()` 查询作用域

2. **AuthorizationService权限检查更新** (`app/Modules/Agent/Services/AuthorizationService.php`):
   - 修正 `canAccessProject()` 方法使用强绑定模式
   - 更新 `getAccessibleProjects()` 方法返回单个项目

3. **文档状态更新**:
   - 更新工具实现状态描述，明确指出Agent模型已支持但工具方法尚未更新
   - 标记需要进一步修正的工具：`create_main_task`、`list_tasks`、`get_questions`

4. **MCP工具方法更新** (`app/Modules/MCP/Tools/`):
   - 修正 `TaskTool::createMainTask()` 方法移除projectId参数，自动使用Agent绑定的项目
   - 修正 `TaskTool::listTasks()` 方法移除projectId参数，增加assignedToMe和limit参数
   - 修正 `GetQuestionsTool::getQuestions()` 方法移除project_id参数，自动使用Agent绑定的项目

### 2025年07月21日 08:45 - 问题管理工具修复 ✅

**修复内容**:
1. **AskQuestionTool重写** (`app/Modules/MCP/Tools/AskQuestionTool.php`):
   - 从旧的接口模式重写为 `#[MCPTool]` 属性模式
   - 移除project_id参数，自动使用Agent绑定的项目
   - 更新依赖注入使用 `AuthenticationService`
   - 实现阻塞式等待回答机制（600秒超时）
   - 每2秒轮询检查回答状态
   - 支持立即返回用户回答或超时状态

2. **CheckAnswerTool移除** (`app/Modules/MCP/Tools/CheckAnswerTool.php`):
   - 删除CheckAnswerTool，因为ask_question已实现阻塞式等待
   - 简化工作流程，无需Agent主动检查回答状态

3. **工具发现统计更新**:
   - 工具总数从8个增加到9个（移除了check_answer）
   - 优化了 `ask_question` 工具的用户体验

**设计改进**: ✅ 实现了更符合用户期望的阻塞式提问机制

### 2025年07月23日 13:15 - 移除错误的get_questions工具 ✅

**移除内容**:
1. **删除GetQuestionsTool** (`app/Modules/MCP/Tools/GetQuestionsTool.php`):
   - 移除错误的 `get_questions` MCP工具
   - 该工具不符合MCP设计原则，Agent应该专注于提问而非查询历史问题
   - 简化MCP工具集，保持功能聚焦

2. **更新工具统计**:
   - 工具总数从9个减少到8个
   - 移除问题管理工具中的 `get_questions` 条目
   - 保留 `ask_question` 工具作为唯一的问题管理工具

3. **文档清理**:
   - 更新MCP工具清单，移除相关文档
   - 清理引用和依赖关系

**设计原则**: ✅ MCP工具应该专注于Agent的核心交互需求，避免不必要的查询功能

### 2025年07月25日 19:10 - 列表维护和文档整理 ✅

**维护内容**:
1. **列表部分完善**:
   - 填充了空白的列表部分，提供了清晰的MCP内容概览
   - 按类型分组：工具(8个)、资源(2个)、未注册内容(4个)
   - 标明了每个项目的状态和推荐程度

2. **文档结构优化**:
   - 移除了重复的ask_question工具描述
   - 更新了版本号到1.3.0
   - 更新了时间戳到当前时间

3. **内容分类明确**:
   - 已注册且推荐使用的MCP工具和资源
   - 未注册或不推荐使用的内容，并说明原因
   - 保持了文档的准确性和实用性

**改进效果**: ✅ 提供了更清晰的MCP内容概览，便于快速了解可用功能

### 2025年07月25日 19:15 - 重大更新：Dbcont模块MCP集成完成 ✅

**更新内容**:
1. **MCP工具数量更新**:
   - 从8个工具增加到11个工具 (+3个Dbcont工具)
   - 新增数据库工具：execute_sql, list_connections, test_connection

2. **MCP资源数量更新**:
   - 从2个资源增加到6个资源 (+4个Dbcont资源)
   - 新增数据库资源：db://connection/{id}, db://connections, sqllog://{agentId}, sqllog://stats/{agentId}

3. **文档结构重组**:
   - 按功能分类重新组织工具和资源列表
   - 任务管理工具(7个) + 交互工具(1个) + 数据库工具(3个)
   - 基础资源(2个) + 数据库资源(4个)

4. **详细描述完善**:
   - 为所有数据库工具添加了完整的参数说明、权限要求、安全机制
   - 为所有数据库资源添加了URI模式、返回格式、权限控制说明
   - 标记了未注册资源为已废弃状态

5. **版本升级**:
   - 版本号从1.3.0升级到2.0.0
   - 反映了Dbcont模块集成带来的重大功能增强

**技术成就**: ✅
- Agent现在具备完整的数据库访问能力
- 支持安全的SQL执行、连接管理、日志查询
- 完善的权限控制和审计机制
- 11个MCP工具 + 6个MCP资源，功能覆盖全面

**设计原则**: ✅ MCP工具专注于Agent的核心交互需求，数据库功能通过安全的工具接口提供

### 2025年07月25日 19:20 - 架构调整：MCP内容统一到MCP模块 ✅

**调整内容**:
1. **文件移动**:
   - 将 `app/Modules/Dbcont/Tools/SqlExecutionTool.php` 移动到 `app/Modules/MCP/Tools/`
   - 将 `app/Modules/Dbcont/Resources/DatabaseConnectionResource.php` 移动到 `app/Modules/MCP/Resources/`
   - 将 `app/Modules/Dbcont/Resources/SqlExecutionLogResource.php` 移动到 `app/Modules/MCP/Resources/`

2. **命名空间更新**:
   - 所有移动的文件命名空间从 `Modules\Dbcont\*` 更新为 `Modules\MCP\*`
   - 保持原有的依赖注入和服务调用不变

3. **配置清理**:
   - 从 `config/mcp.php` 中移除对 `app/Modules/Dbcont/Tools` 和 `app/Modules/Dbcont/Resources` 的发现配置
   - 从 `.env` 文件中移除 Dbcont 相关的 MCP 发现目录
   - 删除空的 Dbcont Tools 和 Resources 目录

4. **文档更新**:
   - 更新所有数据库工具和资源的文件路径引用
   - 保持功能描述和技术规格不变

**架构原则**: ✅
- 所有MCP相关内容统一集中在MCP模块中
- 业务模块（如Dbcont）专注于核心业务逻辑，不包含MCP注册内容
- 保持模块职责清晰，避免跨模块的MCP注册

**验证结果**: ✅
- MCP发现功能正常，所有11个工具和6个资源都被正确识别
- 功能完全不受影响，Agent仍可正常使用所有数据库功能
- 架构更加清晰，符合模块分离原则



