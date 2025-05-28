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
│   ├── TaskDependency.php          # 任务依赖关系
│   ├── TaskAssignment.php          # 任务分配记录
│   └── TaskHistory.php             # 任务历史记录
├── Services/
│   ├── TaskService.php             # 主任务服务
│   ├── SubTaskService.php          # 子任务服务
│   ├── TaskWorkflowService.php     # 工作流服务
│   ├── TaskProgressService.php     # 进度计算服务
│   └── TaskNotificationService.php # 任务通知服务
├── Controllers/
│   ├── TaskController.php          # 主任务控制器
│   ├── SubTaskController.php       # 子任务控制器
│   └── TaskWorkflowController.php  # 工作流控制器
├── Resources/
│   ├── TaskResource.php            # 主任务API资源
│   ├── SubTaskResource.php         # 子任务API资源
│   ├── TaskCollection.php          # 任务集合资源
│   └── TaskProgressResource.php    # 任务进度资源
├── Requests/
│   ├── CreateTaskRequest.php       # 创建主任务请求
│   ├── UpdateTaskRequest.php       # 更新主任务请求
│   ├── CreateSubTaskRequest.php    # 创建子任务请求
│   └── CompleteTaskRequest.php     # 完成任务请求
├── Events/
│   ├── TaskCreated.php             # 主任务创建事件
│   ├── TaskUpdated.php             # 主任务更新事件
│   ├── TaskCompleted.php           # 主任务完成事件
│   ├── SubTaskCreated.php          # 子任务创建事件
│   ├── SubTaskCompleted.php        # 子任务完成事件
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

## 核心服务

### 1. TaskService 主任务服务

```php
<?php

namespace App\Modules\Task\Services;

use App\Modules\Task\Contracts\TaskServiceInterface;

class TaskService implements TaskServiceInterface
{
    public function __construct(
        private SubTaskService $subTaskService,
        private TaskProgressService $progressService,
        private TaskNotificationService $notificationService
    ) {}

    /**
     * 创建主任务
     */
    public function create(array $data, User $creator): Task
    {
        $task = Task::create([
            'title' => $data['title'],
            'description' => $data['description'],
            'priority' => $data['priority'] ?? Task::PRIORITY_MEDIUM,
            'project_id' => $data['project_id'],
            'created_by' => $creator->id,
            'assigned_to' => $data['assigned_to'] ?? null,
            'due_date' => $data['due_date'] ?? null,
            'estimated_hours' => $data['estimated_hours'] ?? null,
            'status' => Task::STATUS_PENDING,
            'completion_percentage' => 0.0,
            'metadata' => $data['metadata'] ?? [],
        ]);

        event(new TaskCreated($task, $creator));

        return $task;
    }

    /**
     * 为Agent创建任务
     */
    public function createForAgent(Agent $agent, array $data): Task
    {
        // 验证Agent权限
        if (!$agent->canCreateTask($data['project_id'])) {
            throw new UnauthorizedException('Agent无权在此项目创建任务');
        }

        return $this->create($data, $agent->user);
    }

    /**
     * 更新任务
     */
    public function update(Task $task, array $data): Task
    {
        $oldStatus = $task->status;

        $task->update($data);

        if ($oldStatus !== $task->status) {
            event(new TaskUpdated($task, $oldStatus));
        }

        return $task;
    }

    /**
     * 完成任务
     */
    public function complete(Task $task, Agent $agent = null): Task
    {
        if (!$task->canBeCompleted()) {
            throw new TaskException('任务还有未完成的子任务，无法标记为完成');
        }

        $task->update([
            'status' => Task::STATUS_COMPLETED,
            'completion_percentage' => 100.0,
            'completed_at' => now(),
        ]);

        event(new TaskCompleted($task, $agent));

        return $task;
    }

    /**
     * 认领任务
     */
    public function claim(Task $task, Agent $agent): Task
    {
        if ($task->status !== Task::STATUS_PENDING) {
            throw new TaskException('只能认领待处理状态的任务');
        }

        $task->update([
            'status' => Task::STATUS_IN_PROGRESS,
            'assigned_to' => $agent->user_id,
        ]);

        return $task;
    }

    /**
     * 获取任务详情（包含子任务）
     */
    public function getTaskWithSubTasks(int $taskId): Task
    {
        return Task::with(['subTasks', 'project', 'creator', 'assignee'])
            ->findOrFail($taskId);
    }

    /**
     * 获取用户任务列表
     */
    public function getUserTasks(User $user, array $filters = []): Collection
    {
        $query = Task::where('assigned_to', $user->id)
            ->orWhere('created_by', $user->id);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['project_id'])) {
            $query->where('project_id', $filters['project_id']);
        }

        if (isset($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        return $query->with(['project', 'subTasks'])->get();
    }

    /**
     * 获取Agent可访问的任务
     */
    public function getAgentTasks(Agent $agent, array $filters = []): Collection
    {
        $projectIds = $agent->allowed_projects ?? [];

        $query = Task::whereIn('project_id', $projectIds);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->with(['project', 'subTasks'])->get();
    }
}
```

### 2. SubTaskService 子任务服务

```php
<?php

namespace App\Modules\Task\Services;

class SubTaskService
{
    /**
     * 为主任务创建子任务
     */
    public function createForTask(Task $parentTask, array $data, Agent $agent): SubTask
    {
        $subTask = SubTask::create([
            'parent_task_id' => $parentTask->id,
            'title' => $data['title'],
            'description' => $data['description'] ?? '',
            'type' => $data['type'],
            'agent_id' => $agent->id,
            'status' => SubTask::STATUS_PENDING,
            'execution_data' => $data['execution_data'] ?? [],
            'estimated_duration' => $data['estimated_duration'] ?? null,
            'max_retries' => $data['max_retries'] ?? 3,
            'retry_count' => 0,
        ]);

        event(new SubTaskCreated($subTask, $agent));

        // 更新主任务进度
        $parentTask->updateProgress();

        return $subTask;
    }

    /**
     * 批量创建子任务
     */
    public function createBatch(Task $parentTask, array $subTasksData, Agent $agent): Collection
    {
        $subTasks = collect();

        foreach ($subTasksData as $data) {
            $subTasks->push($this->createForTask($parentTask, $data, $agent));
        }

        return $subTasks;
    }

    /**
     * 开始执行子任务
     */
    public function start(SubTask $subTask): SubTask
    {
        $subTask->start();

        return $subTask;
    }

    /**
     * 完成子任务
     */
    public function complete(SubTask $subTask, array $resultData = []): SubTask
    {
        $subTask->complete($resultData);

        event(new SubTaskCompleted($subTask));

        // 检查主任务是否可以完成
        $this->checkParentTaskCompletion($subTask->parentTask);

        return $subTask;
    }

    /**
     * 子任务执行失败
     */
    public function fail(SubTask $subTask, string $reason, bool $canRetry = true): SubTask
    {
        $subTask->fail($reason, $canRetry);

        return $subTask;
    }

    /**
     * 获取Agent的子任务列表
     */
    public function getAgentSubTasks(Agent $agent, array $filters = []): Collection
    {
        $query = SubTask::where('agent_id', $agent->id);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['parent_task_id'])) {
            $query->where('parent_task_id', $filters['parent_task_id']);
        }

        return $query->with('parentTask')->get();
    }

    /**
     * 检查主任务完成条件
     */
    private function checkParentTaskCompletion(Task $parentTask): void
    {
        if ($parentTask->canBeCompleted() &&
            $parentTask->status === Task::STATUS_IN_PROGRESS) {

            // 自动完成主任务
            $parentTask->update([
                'status' => Task::STATUS_COMPLETED,
                'completion_percentage' => 100.0,
                'completed_at' => now(),
            ]);

            event(new TaskCompleted($parentTask));
        }
    }
}
```

## 工作流管理

### 任务状态机

```php
<?php

namespace App\Modules\Task\Workflows;

class TaskStateMachine
{
    /**
     * 状态转换规则
     */
    private const TRANSITIONS = [
        Task::STATUS_PENDING => [
            Task::STATUS_IN_PROGRESS,
            Task::STATUS_CANCELLED,
        ],
        Task::STATUS_IN_PROGRESS => [
            Task::STATUS_COMPLETED,
            Task::STATUS_BLOCKED,
            Task::STATUS_CANCELLED,
        ],
        Task::STATUS_BLOCKED => [
            Task::STATUS_IN_PROGRESS,
            Task::STATUS_CANCELLED,
        ],
        Task::STATUS_COMPLETED => [
            // 已完成的任务不能转换到其他状态
        ],
        Task::STATUS_CANCELLED => [
            Task::STATUS_PENDING, // 可以重新激活
        ],
    ];

    /**
     * 检查状态转换是否有效
     */
    public function canTransition(string $fromStatus, string $toStatus): bool
    {
        return in_array($toStatus, self::TRANSITIONS[$fromStatus] ?? []);
    }

    /**
     * 获取可转换的状态列表
     */
    public function getAvailableTransitions(string $currentStatus): array
    {
        return self::TRANSITIONS[$currentStatus] ?? [];
    }

    /**
     * 执行状态转换
     */
    public function transition(Task $task, string $toStatus, Agent $agent = null): Task
    {
        if (!$this->canTransition($task->status, $toStatus)) {
            throw new InvalidTransitionException(
                "无法从 {$task->status} 转换到 {$toStatus}"
            );
        }

        // 特殊规则检查
        if ($toStatus === Task::STATUS_COMPLETED && !$task->canBeCompleted()) {
            throw new TaskException('任务还有未完成的子任务');
        }

        $oldStatus = $task->status;
        $task->update(['status' => $toStatus]);

        event(new TaskStatusChanged($task, $oldStatus, $toStatus, $agent));

        return $task;
    }
}
```

## API控制器

### TaskController 主任务控制器

```php
<?php

namespace App\Modules\Task\Controllers;

class TaskController extends Controller
{
    public function __construct(
        private TaskService $taskService,
        private TaskWorkflowService $workflowService
    ) {}

    /**
     * 获取任务列表
     */
    public function index(Request $request): JsonResponse
    {
        $tasks = $this->taskService->getUserTasks(
            $request->user(),
            $request->only(['status', 'project_id', 'priority'])
        );

        return TaskResource::collection($tasks)->response();
    }

    /**
     * 创建任务
     */
    public function store(CreateTaskRequest $request): JsonResponse
    {
        $task = $this->taskService->create(
            $request->validated(),
            $request->user()
        );

        return new TaskResource($task);
    }

    /**
     * 获取任务详情
     */
    public function show(Task $task): JsonResponse
    {
        $this->authorize('view', $task);

        $taskWithSubTasks = $this->taskService->getTaskWithSubTasks($task->id);

        return new TaskResource($taskWithSubTasks);
    }

    /**
     * 更新任务
     */
    public function update(UpdateTaskRequest $request, Task $task): JsonResponse
    {
        $this->authorize('update', $task);

        $task = $this->taskService->update($task, $request->validated());

        return new TaskResource($task);
    }

    /**
     * 完成任务
     */
    public function complete(Task $task): JsonResponse
    {
        $this->authorize('complete', $task);

        $task = $this->taskService->complete($task);

        return new TaskResource($task);
    }

    /**
     * 认领任务
     */
    public function claim(Task $task): JsonResponse
    {
        $this->authorize('claim', $task);

        // 通过Agent认领（如果是Agent请求）
        $agent = $this->getRequestAgent();
        if ($agent) {
            $task = $this->taskService->claim($task, $agent);
        }

        return new TaskResource($task);
    }
}
```

### SubTaskController 子任务控制器

```php
<?php

namespace App\Modules\Task\Controllers;

class SubTaskController extends Controller
{
    public function __construct(
        private SubTaskService $subTaskService
    ) {}

    /**
     * 为主任务创建子任务
     */
    public function store(CreateSubTaskRequest $request, Task $task): JsonResponse
    {
        $agent = $this->getRequestAgent();
        if (!$agent) {
            return response()->json(['error' => '只有Agent可以创建子任务'], 403);
        }

        $subTask = $this->subTaskService->createForTask(
            $task,
            $request->validated(),
            $agent
        );

        return new SubTaskResource($subTask);
    }

    /**
     * 批量创建子任务
     */
    public function batchStore(Request $request, Task $task): JsonResponse
    {
        $request->validate([
            'sub_tasks' => 'required|array',
            'sub_tasks.*.title' => 'required|string',
            'sub_tasks.*.type' => 'required|string',
        ]);

        $agent = $this->getRequestAgent();
        if (!$agent) {
            return response()->json(['error' => '只有Agent可以创建子任务'], 403);
        }

        $subTasks = $this->subTaskService->createBatch(
            $task,
            $request->sub_tasks,
            $agent
        );

        return SubTaskResource::collection($subTasks)->response();
    }

    /**
     * 开始执行子任务
     */
    public function start(SubTask $subTask): JsonResponse
    {
        $agent = $this->getRequestAgent();
        if (!$agent || $subTask->agent_id !== $agent->id) {
            return response()->json(['error' => '只能操作自己的子任务'], 403);
        }

        $subTask = $this->subTaskService->start($subTask);

        return new SubTaskResource($subTask);
    }

    /**
     * 完成子任务
     */
    public function complete(CompleteSubTaskRequest $request, SubTask $subTask): JsonResponse
    {
        $agent = $this->getRequestAgent();
        if (!$agent || $subTask->agent_id !== $agent->id) {
            return response()->json(['error' => '只能操作自己的子任务'], 403);
        }

        $subTask = $this->subTaskService->complete(
            $subTask,
            $request->validated()['result_data'] ?? []
        );

        return new SubTaskResource($subTask);
    }

    /**
     * 标记子任务失败
     */
    public function fail(Request $request, SubTask $subTask): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string',
            'can_retry' => 'boolean',
        ]);

        $agent = $this->getRequestAgent();
        if (!$agent || $subTask->agent_id !== $agent->id) {
            return response()->json(['error' => '只能操作自己的子任务'], 403);
        }

        $subTask = $this->subTaskService->fail(
            $subTask,
            $request->reason,
            $request->boolean('can_retry', true)
        );

        return new SubTaskResource($subTask);
    }

    /**
     * 获取Agent的子任务列表
     */
    public function agentTasks(Request $request): JsonResponse
    {
        $agent = $this->getRequestAgent();
        if (!$agent) {
            return response()->json(['error' => '需要Agent身份'], 403);
        }

        $subTasks = $this->subTaskService->getAgentSubTasks(
            $agent,
            $request->only(['status', 'type', 'parent_task_id'])
        );

        return SubTaskResource::collection($subTasks)->response();
    }
}
```

## 事件和监听器

### 任务事件

```php
<?php

namespace App\Modules\Task\Events;

class TaskCreated
{
    public function __construct(
        public readonly Task $task,
        public readonly User $creator
    ) {}
}

class SubTaskCreated
{
    public function __construct(
        public readonly SubTask $subTask,
        public readonly Agent $agent
    ) {}
}

class SubTaskCompleted
{
    public function __construct(
        public readonly SubTask $subTask
    ) {}
}

class TaskCompleted
{
    public function __construct(
        public readonly Task $task,
        public readonly ?Agent $completedBy = null
    ) {}
}

class TaskProgressUpdated
{
    public function __construct(
        public readonly Task $task,
        public readonly float $oldProgress,
        public readonly float $newProgress
    ) {}
}
```

### 事件监听器

```php
<?php

namespace App\Modules\Task\Listeners;

class CheckTaskCompletion
{
    public function handle(SubTaskCompleted $event): void
    {
        $parentTask = $event->subTask->parentTask;

        // 检查是否所有子任务都完成
        if ($parentTask->canBeCompleted() &&
            $parentTask->status === Task::STATUS_IN_PROGRESS) {

            $parentTask->update([
                'status' => Task::STATUS_COMPLETED,
                'completion_percentage' => 100.0,
                'completed_at' => now(),
            ]);

            event(new TaskCompleted($parentTask));
        }
    }
}

class UpdateTaskProgress
{
    public function handle(SubTaskCompleted $event): void
    {
        $parentTask = $event->subTask->parentTask;
        $oldProgress = $parentTask->completion_percentage;

        $parentTask->updateProgress();

        if ($oldProgress !== $parentTask->completion_percentage) {
            event(new TaskProgressUpdated(
                $parentTask,
                $oldProgress,
                $parentTask->completion_percentage
            ));
        }
    }
}

class NotifyTaskAssignee
{
    public function handle(TaskCompleted $event): void
    {
        if ($event->task->assignee) {
            // 发送任务完成通知
            $event->task->assignee->notify(
                new TaskCompletedNotification($event->task)
            );
        }
    }
}
```

## MCP集成

### 任务相关的MCP Resources

```php
<?php

namespace App\Modules\Mcp\Resources;

class TaskResource implements ResourceInterface
{
    /**
     * 支持的URI模式
     * - task://list
     * - task://{id}
     * - task://project/{project_id}
     * - task://agent/{agent_id}/subtasks
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
            'project' => $this->getProjectTasks($parsed['project_id'], $params),
            'agent_subtasks' => $this->getAgentSubTasks($parsed['agent_id'], $params),
            default => throw new InvalidUriException("Unsupported URI: {$uri}")
        };
    }

    private function getAgentSubTasks(string $agentId, array $params): array
    {
        $agent = Agent::findOrFail($agentId);
        $subTasks = SubTask::where('agent_id', $agent->id)
            ->with('parentTask')
            ->get();

        return [
            'sub_tasks' => $subTasks->map(function ($subTask) {
                return [
                    'id' => $subTask->id,
                    'title' => $subTask->title,
                    'status' => $subTask->status,
                    'type' => $subTask->type,
                    'parent_task' => [
                        'id' => $subTask->parentTask->id,
                        'title' => $subTask->parentTask->title,
                    ],
                    'progress' => $subTask->getProgressDescription(),
                    'created_at' => $subTask->created_at->toISOString(),
                ];
            })->toArray(),
        ];
    }
}
```

### 任务管理MCP Tool

```php
<?php

namespace App\Modules\Mcp\Tools;

class TaskManagementTool implements ToolInterface
{
    public function getName(): string
    {
        return 'task_management';
    }

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'action' => [
                    'type' => 'string',
                    'enum' => [
                        'create_main_task',
                        'create_sub_task',
                        'start_sub_task',
                        'complete_sub_task',
                        'fail_sub_task',
                        'get_task_progress'
                    ]
                ],
                'task_id' => ['type' => 'integer'],
                'title' => ['type' => 'string'],
                'description' => ['type' => 'string'],
                'type' => ['type' => 'string'],
                'execution_data' => ['type' => 'object'],
                'result_data' => ['type' => 'object'],
            ],
            'required' => ['action']
        ];
    }

    public function execute(array $arguments, string $agentId): array
    {
        $agent = Agent::findOrFail($agentId);

        return match($arguments['action']) {
            'create_sub_task' => $this->createSubTask($agent, $arguments),
            'start_sub_task' => $this->startSubTask($agent, $arguments),
            'complete_sub_task' => $this->completeSubTask($agent, $arguments),
            'fail_sub_task' => $this->failSubTask($agent, $arguments),
            'get_task_progress' => $this->getTaskProgress($arguments),
            default => throw new McpException('Invalid action', 400)
        };
    }

    private function createSubTask(Agent $agent, array $args): array
    {
        $parentTask = Task::findOrFail($args['task_id']);

        $subTask = app(SubTaskService::class)->createForTask(
            $parentTask,
            [
                'title' => $args['title'],
                'description' => $args['description'] ?? '',
                'type' => $args['type'],
                'execution_data' => $args['execution_data'] ?? [],
            ],
            $agent
        );

        return [
            'sub_task_id' => $subTask->id,
            'status' => $subTask->status,
            'message' => '子任务创建成功',
        ];
    }

    private function completeSubTask(Agent $agent, array $args): array
    {
        $subTask = SubTask::where('id', $args['task_id'])
            ->where('agent_id', $agent->id)
            ->firstOrFail();

        app(SubTaskService::class)->complete(
            $subTask,
            $args['result_data'] ?? []
        );

        return [
            'sub_task_id' => $subTask->id,
            'status' => $subTask->fresh()->status,
            'parent_task_progress' => $subTask->parentTask->fresh()->completion_percentage,
            'message' => '子任务完成',
        ];
    }
}
```

## 配置管理

```php
// config/task.php
return [
    'defaults' => [
        'priority' => env('TASK_DEFAULT_PRIORITY', 'medium'),
        'max_sub_tasks' => env('TASK_MAX_SUB_TASKS', 50),
        'auto_complete_main_task' => env('TASK_AUTO_COMPLETE', true),
    ],

    'sub_tasks' => [
        'max_retries' => env('SUB_TASK_MAX_RETRIES', 3),
        'default_timeout' => env('SUB_TASK_TIMEOUT', 3600), // 1小时
        'cleanup_completed_after' => env('SUB_TASK_CLEANUP_DAYS', 30),
    ],

    'notifications' => [
        'task_completed' => env('TASK_NOTIFY_COMPLETION', true),
        'task_overdue' => env('TASK_NOTIFY_OVERDUE', true),
        'sub_task_failed' => env('TASK_NOTIFY_SUB_TASK_FAILURE', true),
    ],

    'workflow' => [
        'auto_transition' => env('TASK_AUTO_TRANSITION', true),
        'require_approval' => env('TASK_REQUIRE_APPROVAL', false),
        'parallel_sub_tasks' => env('TASK_PARALLEL_SUB_TASKS', true),
    ],
];
```

---

**相关文档**：
- [MCP协议模块](./mcp.md)
- [Agent代理模块](./agent.md)
- [项目模块](./project.md)
