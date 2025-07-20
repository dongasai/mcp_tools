# MCP Tools 项目 - 'MCP内容'清单

**更新时间**: 2025年07月21日 05:24:15 CST
**版本**: 1.1.0
**基于**: php-mcp/laravel 包

## 概述

本文档详细列出了 MCP Tools 项目中所有的 MCP（Model Context Protocol）的内容，包括工具、资源、提示、模板。每个条目都标识了其注册状态、实现状态和规划状态。

> 注意MCP内容只有‘工具/资源/提示/模板‘四类，其他的都不是MCP内容

## 当前状态

### 发现统计（通过 `php artisan mcp:list` 获取）
- **工具 (Tools)**: 8 个已注册
- **资源 (Resources)**: 1 个已注册
- **提示 (Prompts)**: 0 个
- **模板 (Templates)**: 0 个

### 实现统计
- **已实现工具**: 8 个
- **已实现资源**: 3 个（2个未被发现）




## 1. MCP 工具 (Tools)

### 1.1 任务管理工具 ✅ 已注册/已实现

#### create_main_task ⚠️ 已注册/需修正
- **文件**: `app/Modules/Mcp/Tools/TaskTool.php`
- **方法**: `createMainTask()`
- **注册状态**: ✅ 已通过属性自动发现注册
- **实现状态**: ⚠️ 需修正（当前需要projectId参数，但应该自动使用Agent的项目）
- **描述**: 创建主任务
- **当前参数**:
  - `projectId` (string): 项目ID ⚠️ **应该移除，Agent和项目强绑定**
  - `title` (string): 任务标题
  - `description` (string, 可选): 任务描述
  - `priority` (string, 可选): 优先级 (默认: medium)
- **建议参数**:
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

#### list_tasks ⚠️ 已注册/需修正
- **文件**: `app/Modules/Mcp/Tools/TaskTool.php`
- **方法**: `listTasks()`
- **注册状态**: ✅ 已通过属性自动发现注册
- **实现状态**: ⚠️ 需修正（当前有projectId参数，但应该自动使用Agent的项目）
- **描述**: 获取任务列表
- **当前参数**:
  - `status` (string, 可选): 状态过滤
  - `type` (string, 可选): 任务类型过滤
  - `projectId` (string, 可选): 项目ID过滤 ⚠️ **应该移除，Agent和项目强绑定**
- **建议参数**:
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

#### get_questions ⚠️ 已注册/需修正
- **文件**: `app/Modules/Mcp/Tools/GetQuestionsTool.php`
- **方法**: `getQuestions()`
- **注册状态**: ✅ 已通过属性自动发现注册
- **实现状态**: ⚠️ 需修正（当前有project_id参数，但应该自动使用Agent的项目）
- **描述**: 获取问题列表，支持多种过滤条件
- **当前参数**:
  - `status` (string, 可选): 问题状态 (PENDING/ANSWERED/IGNORED)
  - `priority` (string, 可选): 优先级 (URGENT/HIGH/MEDIUM/LOW)
  - `question_type` (string, 可选): 问题类型 (CHOICE/FEEDBACK)
  - `task_id` (int, 可选): 任务ID过滤
  - `project_id` (int, 可选): 项目ID过滤 ⚠️ **应该移除，Agent和项目强绑定**
  - `limit` (int, 可选): 返回数量限制 (默认: 10)
  - `only_mine` (bool, 可选): 是否只返回当前Agent的问题 (默认: true)
  - `include_expired` (bool, 可选): 是否包含已过期的问题 (默认: false)
- **建议参数**:
  - `status` (string, 可选): 问题状态 (PENDING/ANSWERED/IGNORED)
  - `priority` (string, 可选): 优先级 (URGENT/HIGH/MEDIUM/LOW)
  - `question_type` (string, 可选): 问题类型 (CHOICE/FEEDBACK)
  - `task_id` (int, 可选): 任务ID过滤
  - `limit` (int, 可选): 返回数量限制 (默认: 10)
  - `only_mine` (bool, 可选): 是否只返回当前Agent的问题 (默认: true)
  - `include_expired` (bool, 可选): 是否包含已过期的问题 (默认: false)
- **权限**: 基于Agent身份和项目权限，自动限制为Agent绑定的项目

### 1.3 项目管理工具 ❌ 未注册/已实现 ⚠️ 不合理的，MCP不能进行项目管理

#### project_manager ❌ 未注册/已实现 
- **文件**: `app/Modules/Mcp/Tools/ProjectTool.php`
- **注册状态**: ❌ 未被MCP发现（缺少属性标记）
- **实现状态**: ✅ 完整实现
- **描述**: 项目管理工具，支持创建、更新、删除、查询项目
- **参数**: 支持多种操作类型和项目数据
- **权限**: 基于Agent身份和项目权限

### 1.4 Agent管理工具 ❌ 未注册/已实现 ⚠️ 不合理的，MCP不能进行Agent管理

#### agent_manager ❌ 未注册/已实现
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



