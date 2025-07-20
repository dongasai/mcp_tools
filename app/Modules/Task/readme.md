# Task 任务模块

## 概述

Task任务模块是MCP Tools系统的核心业务模块，负责管理任务的完整生命周期。该模块实现了主任务和子任务的层次化管理机制，其中主任务由用户创建和管理，子任务由AI Agent自主创建和维护以完成主任务。

## 核心概念

### 主任务 (Main Task)
- **定义**：由用户创建和管理的顶层任务
- **特点**：面向业务目标，描述需要完成的具体工作
- **管理者**：人类用户
- **完成条件**：所有关联的子任务都完成后，主任务才能标记为完成

### 子任务 (Sub Task)
- **定义**：由AI Agent为完成主任务而自主创建的执行步骤
- **特点**：面向技术实现，描述具体的执行动作
- **管理者**：AI Agent
- **完成条件**：Agent完成具体的技术操作后可直接标记完成

## 职责范围

### 1. 主任务管理
- 主任务的CRUD操作
- 任务状态流转控制
- 任务优先级管理
- 任务分配和认领

### 2. 子任务管理
- 子任务的自动创建机制
- 子任务执行状态跟踪
- 子任务与主任务的关联管理
- 子任务完成度统计

### 3. 任务层次管理
- 主子任务关系维护
- 任务依赖关系处理
- 任务完成条件验证
- 任务进度计算

### 4. 工作流管理
- 任务状态机定义
- 自动化流程触发
- 任务通知和提醒
- 任务历史记录

## 目录结构

```
app/Modules/Task/
├── Models/
│   ├── Task.php                    # 任务模型（主任务）
│   ├── SubTask.php                 # 子任务模型
│   ├── TaskComment.php             # 任务评论模型
│   ├── TaskDependency.php          # 任务依赖关系
│   ├── TaskAssignment.php          # 任务分配记录
│   └── TaskHistory.php             # 任务历史记录
├── Services/
│   ├── TaskService.php             # 主任务服务
│   ├── SubTaskService.php          # 子任务服务
│   ├── TaskCommentService.php      # 任务评论服务
│   ├── TaskWorkflowService.php     # 工作流服务
│   ├── TaskProgressService.php     # 进度计算服务
│   └── TaskNotificationService.php # 任务通知服务
├── Controllers/
│   ├── TaskController.php          # 主任务控制器
│   ├── SubTaskController.php       # 子任务控制器
│   ├── TaskCommentController.php   # 任务评论控制器
│   └── TaskWorkflowController.php  # 工作流控制器
├── Resources/
│   ├── TaskResource.php            # 主任务API资源
│   ├── SubTaskResource.php         # 子任务API资源
│   ├── TaskCommentResource.php     # 任务评论API资源
│   ├── TaskCollection.php          # 任务集合资源
│   └── TaskProgressResource.php    # 任务进度资源
├── Requests/
│   ├── CreateTaskRequest.php       # 创建主任务请求
│   ├── UpdateTaskRequest.php       # 更新主任务请求
│   ├── CreateSubTaskRequest.php    # 创建子任务请求
│   ├── CreateCommentRequest.php    # 创建评论请求
│   ├── UpdateCommentRequest.php    # 更新评论请求
│   └── CompleteTaskRequest.php     # 完成任务请求
├── Events/
│   ├── TaskCreated.php             # 主任务创建事件
│   ├── TaskUpdated.php             # 主任务更新事件
│   ├── TaskCompleted.php           # 主任务完成事件
│   ├── SubTaskCreated.php          # 子任务创建事件
│   ├── SubTaskCompleted.php        # 子任务完成事件
│   ├── TaskCommentCreated.php      # 任务评论创建事件
│   ├── TaskCommentUpdated.php      # 任务评论更新事件
│   └── TaskProgressUpdated.php     # 任务进度更新事件
├── Listeners/
│   ├── CheckTaskCompletion.php     # 检查任务完成状态
│   ├── UpdateTaskProgress.php      # 更新任务进度
│   ├── NotifyTaskAssignee.php      # 通知任务负责人
│   └── LogTaskActivity.php         # 记录任务活动
├── Workflows/
│   ├── TaskStateMachine.php        # 任务状态机
│   ├── SubTaskWorkflow.php         # 子任务工作流
│   └── TaskCompletionWorkflow.php  # 任务完成工作流
├── Observers/
│   ├── TaskObserver.php            # 主任务观察者
│   └── SubTaskObserver.php         # 子任务观察者
├── Policies/
│   ├── TaskPolicy.php              # 主任务访问策略
│   └── SubTaskPolicy.php           # 子任务访问策略
└── Contracts/
    ├── TaskServiceInterface.php    # 任务服务接口
    ├── WorkflowInterface.php       # 工作流接口
    └── ProgressCalculatorInterface.php # 进度计算接口
```

## 数据模型设计

### Task 主任务模型

**核心属性**：
- `title` - 任务标题
- `description` - 任务描述
- `status` - 任务状态
- `priority` - 任务优先级
- `project_id` - 所属项目ID
- `created_by` - 创建者ID
- `assigned_to` - 负责人ID
- `due_date` - 截止时间
- `estimated_hours` - 预估工时
- `actual_hours` - 实际工时
- `completion_percentage` - 完成百分比
- `metadata` - 元数据信息

**状态定义**：
- `pending` - 待处理
- `in_progress` - 进行中
- `blocked` - 阻塞
- `completed` - 已完成
- `cancelled` - 已取消

**优先级定义**：
- `low` - 低优先级
- `medium` - 中等优先级
- `high` - 高优先级
- `urgent` - 紧急

**关联关系**：
- 与SubTask的一对多关系
- 与Project的多对一关系
- 与User的多对一关系（创建者和负责人）

**核心方法**：
- 子任务管理和查询
- 完成条件检查
- 进度计算和更新
- 过期状态检查

### SubTask 子任务模型

**核心属性**：
- `parent_task_id` - 父任务ID
- `title` - 子任务标题
- `description` - 子任务描述
- `status` - 执行状态
- `type` - 子任务类型
- `agent_id` - 执行Agent ID
- `execution_data` - 执行参数数据
- `result_data` - 执行结果数据
- `started_at` - 开始执行时间
- `completed_at` - 完成时间
- `estimated_duration` - 预估执行时长（秒）
- `actual_duration` - 实际执行时长（秒）
- `retry_count` - 重试次数
- `max_retries` - 最大重试次数

**状态定义**：
- `pending` - 待执行
- `running` - 执行中
- `completed` - 已完成
- `failed` - 执行失败
- `cancelled` - 已取消
- `retrying` - 重试中

**类型定义**：
- `code_analysis` - 代码分析
- `file_operation` - 文件操作
- `api_call` - API调用
- `data_processing` - 数据处理
- `github_operation` - GitHub操作
- `validation` - 验证检查

**关联关系**：
- 与Task的多对一关系（父任务）
- 与Agent的多对一关系（执行者）

**核心功能**：
- 执行状态管理
- 重试机制控制
- 执行时间统计
- 结果数据存储
- 进度描述生成

### TaskComment 任务评论模型

**核心属性**：
- `task_id` - 关联任务ID
- `user_id` - 评论用户ID（可为空，Agent评论时为空）
- `agent_id` - 评论Agent ID（可为空，用户评论时为空）
- `content` - 评论内容
- `comment_type` - 评论类型
- `parent_comment_id` - 父评论ID（支持回复）
- `metadata` - 元数据信息
- `is_internal` - 是否为内部评论
- `is_system` - 是否为系统评论
- `attachments` - 附件信息

**评论类型定义**：
- `general` - 一般评论
- `status_update` - 状态更新说明
- `progress_report` - 进度报告
- `issue_report` - 问题报告
- `solution` - 解决方案
- `question` - 提问
- `answer` - 回答
- `system` - 系统通知

**关联关系**：
- 与Task的多对一关系
- 与User的多对一关系（用户评论）
- 与Agent的多对一关系（Agent评论）
- 与TaskComment的自关联关系（回复功能）

**核心功能**：
- 支持Markdown格式内容
- 支持@提及功能
- 支持文件附件
- 支持评论回复
- 支持评论编辑和删除
- 支持评论点赞/反应
- 自动记录评论时间和修改历史

## 工作流管理

### 任务状态机

**状态转换规则**：
- `pending` → `in_progress`, `cancelled`
- `in_progress` → `completed`, `blocked`, `cancelled`
- `blocked` → `in_progress`, `cancelled`
- `completed` → 无法转换（终态）
- `cancelled` → `pending`（可重新激活）

**核心功能**：
- 状态转换有效性验证
- 特殊规则检查（如完成条件验证）
- 状态变更事件触发
- 可用转换状态查询

**业务规则**：
- 主任务只有在所有子任务完成后才能标记为完成
- 已完成和已取消的任务有不同的重新激活规则
- 状态转换会自动触发相关事件和通知

## 事件系统

### 核心事件类型

**任务生命周期事件**：
- `TaskCreated` - 任务创建事件
- `TaskUpdated` - 任务更新事件
- `TaskCompleted` - 任务完成事件
- `TaskDeleted` - 任务删除事件
- `TaskStatusChanged` - 任务状态变更事件

**子任务事件**：
- `SubTaskCreated` - 子任务创建事件
- `SubTaskCompleted` - 子任务完成事件
- `SubTaskFailed` - 子任务失败事件

**评论事件**：
- `TaskCommentCreated` - 评论创建事件
- `TaskCommentUpdated` - 评论更新事件
- `TaskCommentDeleted` - 评论删除事件

**进度事件**：
- `TaskProgressUpdated` - 任务进度更新事件
- `TaskAgentChanged` - 任务Agent变更事件

### 事件监听器功能

**自动化处理**：
- 子任务完成时自动检查主任务完成条件
- 任务状态变更时自动更新相关统计
- 评论创建时自动处理@提及通知

**通知机制**：
- 任务完成通知相关人员
- 状态变更通知任务负责人
- 评论回复通知原作者

**数据同步**：
- 自动更新任务进度百分比
- 同步任务统计数据
- 记录操作审计日志

## MCP协议集成

### Resource URI 支持

**任务资源访问**：
- `task://list` - 获取任务列表
- `task://{id}` - 获取特定任务详情
- `task://{id}/comments` - 获取任务评论
- `task://project/{project_id}` - 获取项目任务
- `task://agent/{agent_id}/subtasks` - 获取Agent子任务

### Tool Actions

**任务管理操作**：
- `create_main_task` - 创建主任务
- `create_sub_task` - 创建子任务
- `start_sub_task` - 开始子任务
- `complete_sub_task` - 完成子任务
- `fail_sub_task` - 标记子任务失败
- `get_task_progress` - 获取任务进度

**评论管理操作**：
- `add_comment` - 添加评论
- `get_comments` - 获取评论列表
- `reply_comment` - 回复评论

### Agent交互场景

**进度报告**：
```json
{
    "action": "add_comment",
    "task_id": 123,
    "content": "已完成代码分析，发现3个潜在问题需要修复",
    "comment_type": "progress_report"
}
```

**问题咨询**：
```json
{
    "action": "add_comment",
    "task_id": 123,
    "content": "在实现支付功能时遇到安全策略问题，需要您确认使用哪种加密方式？",
    "comment_type": "question"
}
```

**任务完成**：
```json
{
    "action": "complete_sub_task",
    "task_id": 456,
    "result_data": {
        "files_modified": ["src/Payment.php", "tests/PaymentTest.php"],
        "tests_passed": true,
        "performance_improvement": "40%"
    }
}
```

## 配置管理

### 核心配置项

**默认设置**：
- 任务默认优先级
- 最大子任务数量限制
- 自动完成主任务开关

**子任务配置**：
- 最大重试次数
- 默认超时时间
- 完成任务清理周期

**通知配置**：
- 任务完成通知
- 任务逾期通知
- 子任务失败通知
- 评论创建通知
- @提及通知

**评论系统配置**：
- 评论功能开关
- 评论最大长度
- Markdown支持
- 附件支持
- @提及功能
- 回复功能
- 编辑权限和时间限制
- 删除权限和软删除

**工作流配置**：
- 自动状态转换
- 审批要求
- 并行子任务支持

---

**相关文档**：
- [MCP协议模块](./MCP协议概述.md)
- [Agent代理模块](./agent.md)
- [项目模块](./project.md)
