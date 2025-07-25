# MCP Tools 项目 - 'MCP内容'清单

**更新时间**: 2025年07月25日 19:10:00 CST
**版本**: 1.3.0
**基于**: php-mcp/laravel 包

## 概述

本文档详细列出了 MCP Tools 项目中所有的 MCP（Model Context Protocol）的内容，包括工具、资源、提示、模板。每个条目都标识了其注册状态、实现状态和规划状态。

> 注意MCP内容只有‘工具/资源/提示/模板‘四类，其他的都不是MCP内容

## 当前状态

### 发现统计（通过 `php artisan mcp:list` 获取）
- **工具 (Tools)**: 8 个已注册
- **资源 (Resources)**: 2 个已注册
- **提示 (Prompts)**: 0 个
- **模板 (Templates)**: 0 个

### 实现统计
- **已实现工具**: 8 个
- **已实现资源**: 4 个（2个未被发现）

## 列表

### MCP工具 (8个已注册)
1. **create_main_task** - 创建主任务 ✅
2. **create_sub_task** - 创建子任务 ✅
3. **list_tasks** - 获取任务列表 ✅
4. **get_task** - 获取任务详情 ✅
5. **complete_task** - 完成任务 ✅
6. **add_comment** - 添加评论 ✅
7. **get_assigned_tasks** - 获取分配给当前Agent的任务 ✅
8. **ask_question** - Agent向用户提问（阻塞式等待回答） ✅

### MCP资源 (2个已注册)
1. **time://get2** - 获取当前时间 ✅
2. **myinfo://get** - 获取当前Agent和项目信息 ✅

### 未注册但已实现的内容 (不推荐使用)
1. **project_manager** - 项目管理工具 ❌ (不合理，MCP不应进行项目管理)
2. **agent_manager** - Agent管理工具 ❌ (不合理，MCP不应进行Agent管理)
3. **project://{path}** - 项目资源 ❌ (未注册)
4. **task://{path}** - 任务资源 ❌ (未注册)



## 1. MCP 工具 (Tools)


### 1.1 任务管理工具 ✅ 已注册/已实现

#### create_main_task ✅ 已注册/已实现
- **文件**: `app/Modules/Mcp/Tools/TaskTool.php`
- **方法**: `createMainTask()`
- **注册状态**: ✅ 已通过属性自动发现注册
- **实现状态**: ✅ 已修正（自动使用Agent绑定的项目）
- **描述**: 创建主任务
- **参数**:
  - `title` (string): 任务标题
  - `description` (string, 可选): 任务描述
  - `priority` (string, 可选): 优先级 (默认: medium)
- **权限**: 需要 `create_task` 权限，自动使用Agent绑定的项目

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
- **实现状态**: ✅ 已修正（自动使用Agent绑定的项目）
- **描述**: 获取任务列表
- **参数**:
  - `status` (string, 可选): 状态过滤
  - `type` (string, 可选): 任务类型过滤
  - `assignedToMe` (bool, 可选): 是否只显示分配给当前Agent的任务
  - `limit` (int, 可选): 返回数量限制 (默认: 20)
- **权限**: 基于Agent的项目访问权限，自动限制为Agent绑定的项目

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

#### ask_question ✅ 已注册/已实现
- **文件**: `app/Modules/Mcp/Tools/AskQuestionTool.php`
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
- **文件**: `app/Modules/Mcp/Tools/ProjectTool.php`
- **注册状态**: ❌ 未被MCP发现（缺少属性标记）
- **实现状态**: ✅ 完整实现
- **描述**: 项目管理工具，支持创建、更新、删除、查询项目
- **参数**: 支持多种操作类型和项目数据
- **权限**: 基于Agent身份和项目权限

### 1.4 Agent管理工具 ❌ 未注册/已实现 ⚠️ 不合理的，MCP不能进行Agent管理

#### agent_manager ❌ 未注册/已实现 ⚠️ 不合理的，MCP不能进行Agent管理
- **文件**: `app/Modules/Mcp/Tools/AgentTool.php`
- **注册状态**: ❌ 未被MCP发现（缺少属性标记）
- **实现状态**: ✅ 完整实现
- **描述**: Agent管理工具，支持查询、更新Agent信息和权限
- **参数**: 支持多种操作类型和Agent数据
- **权限**: 基于Agent身份验证

### 1.5 问题批量操作工具 ❌ 未注册/已实现 ⚠️ 不合理的，问题对Agent来说就是一问一答，其他的都是垃圾



### 1.6 时间工具 ✅ 已注册/已实现

#### time://get2 ✅ 已注册/已实现
- **文件**: `app/Modules/Mcp/Tools/Time2Tool.php`
- **方法**: `getTime2()`
- **注册状态**: ✅ 已通过属性自动发现注册
- **实现状态**: ✅ 完整实现
- **描述**: 获取当前时间（作为资源提供）
- **返回**: JSON格式的时间信息





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

### 2.2 Agent信息资源 ✅ 已注册/已实现

#### myinfo://get ✅ 已注册/已实现
- **文件**: `app/Modules/Mcp/Resources/MyInfoResource.php`
- **方法**: `getMyInfo()`
- **注册状态**: ✅ 已通过属性自动发现注册
- **实现状态**: ✅ 完整实现
- **名称**: myInfo
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

### 2.3 项目资源 ❌ 未注册/已实现

#### project://{path} ❌ 未注册/已实现
- **文件**: `app/Modules/Mcp/Resources/ProjectResource.php`
- **注册状态**: ❌ 未被MCP发现（缺少属性标记）
- **实现状态**: ✅ 完整实现
- **URI模式**: `project://{path}`
- **描述**: 项目信息访问和管理
- **功能**: 支持项目列表、详情、创建、更新等操作
- **权限**: 基于Agent身份和项目访问权限

### 2.4 任务资源 ❌ 未注册/已实现

#### task://{path} ❌ 未注册/已实现
- **文件**: `app/Modules/Mcp/Resources/TaskResource.php`
- **注册状态**: ❌ 未被MCP发现（缺少属性标记）
- **实现状态**: ✅ 完整实现
- **URI模式**: `task://{path}`
- **描述**: 任务信息访问和管理
- **功能**: 支持任务列表、详情、状态更新等操作
- **权限**: 基于Agent身份和任务访问权限

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

4. **MCP工具方法更新** (`app/Modules/Mcp/Tools/`):
   - 修正 `TaskTool::createMainTask()` 方法移除projectId参数，自动使用Agent绑定的项目
   - 修正 `TaskTool::listTasks()` 方法移除projectId参数，增加assignedToMe和limit参数
   - 修正 `GetQuestionsTool::getQuestions()` 方法移除project_id参数，自动使用Agent绑定的项目

### 2025年07月21日 08:45 - 问题管理工具修复 ✅

**修复内容**:
1. **AskQuestionTool重写** (`app/Modules/Mcp/Tools/AskQuestionTool.php`):
   - 从旧的接口模式重写为 `#[McpTool]` 属性模式
   - 移除project_id参数，自动使用Agent绑定的项目
   - 更新依赖注入使用 `AuthenticationService`
   - 实现阻塞式等待回答机制（600秒超时）
   - 每2秒轮询检查回答状态
   - 支持立即返回用户回答或超时状态

2. **CheckAnswerTool移除** (`app/Modules/Mcp/Tools/CheckAnswerTool.php`):
   - 删除CheckAnswerTool，因为ask_question已实现阻塞式等待
   - 简化工作流程，无需Agent主动检查回答状态

3. **工具发现统计更新**:
   - 工具总数从8个增加到9个（移除了check_answer）
   - 优化了 `ask_question` 工具的用户体验

**设计改进**: ✅ 实现了更符合用户期望的阻塞式提问机制

### 2025年07月23日 13:15 - 移除错误的get_questions工具 ✅

**移除内容**:
1. **删除GetQuestionsTool** (`app/Modules/Mcp/Tools/GetQuestionsTool.php`):
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



