<?php

namespace App\Modules\Task\Services;

use App\Modules\Task\Models\Task;
use App\Modules\User\Models\User;
use App\Modules\Agent\Models\Agent;
use App\Modules\Project\Models\Project;
use App\Modules\Core\Contracts\LogInterface;
use App\Modules\Core\Contracts\EventInterface;
use App\Modules\Core\Validators\SimpleValidator;
use App\Modules\Task\Helpers\TaskValidationHelper;
use App\Modules\Task\Enums\TASKSTATUS;
use App\Modules\Task\Enums\TASKTYPE;
use App\Modules\Task\Enums\TASKPRIORITY;
use Illuminate\Support\Collection;

class TaskService
{
    protected LogInterface $logger;
    protected EventInterface $eventDispatcher;

    public function __construct(
        LogInterface $logger,
        EventInterface $eventDispatcher
    ) {
        $this->logger = $logger;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * 创建任务
     */
    public function create(User $user, array $data): Task
    {
        // 验证数据
        $validatedData = SimpleValidator::check($data, TaskValidationHelper::getCreateTaskRules());

        if (empty($validatedData)) {
            $validator = SimpleValidator::make($data, [
                'title' => 'required|string|min:2|max:255',
                'description' => 'string|max:2000',
                'type' => 'string|in:main,sub,milestone,bug,feature,improvement',
                'priority' => 'string|in:low,medium,high,urgent',
                'project_id' => 'integer',
                'agent_id' => 'integer',
                'parent_task_id' => 'integer',
                'assigned_to' => 'string|max:255',
                'due_date' => 'date',
                'estimated_hours' => 'numeric|min:0',
                'tags' => 'array',
            ]);
            throw new \InvalidArgumentException('Validation failed: ' . $validator->getFirstError());
        }

        // 验证项目权限
        if (isset($validatedData['project_id'])) {
            $project = Project::find($validatedData['project_id']);
            if (!$project || $project->user_id !== $user->id) {
                throw new \InvalidArgumentException('Invalid project or insufficient permissions');
            }
        }

        // 验证Agent权限
        if (isset($validatedData['agent_id'])) {
            $agent = Agent::find($validatedData['agent_id']);
            if (!$agent || $agent->user_id !== $user->id) {
                throw new \InvalidArgumentException('Invalid agent or insufficient permissions');
            }
        }

        // 验证父任务权限
        if (isset($validatedData['parent_task_id'])) {
            $parentTask = Task::find($validatedData['parent_task_id']);
            if (!$parentTask || $parentTask->user_id !== $user->id) {
                throw new \InvalidArgumentException('Invalid parent task or insufficient permissions');
            }
            // 子任务自动继承父任务的项目
            $validatedData['project_id'] = $parentTask->project_id;
            $validatedData['type'] = TASKTYPE::SUB->value;
        }

        // 创建任务
        $task = Task::create([
            'user_id' => $user->id,
            'title' => $validatedData['title'],
            'description' => $validatedData['description'] ?? null,
            'type' => $validatedData['type'] ?? TASKTYPE::MAIN->value,
            'priority' => $validatedData['priority'] ?? TASKPRIORITY::MEDIUM->value,
            'project_id' => $validatedData['project_id'] ?? null,
            'agent_id' => $validatedData['agent_id'] ?? null,
            'parent_task_id' => $validatedData['parent_task_id'] ?? null,
            'assigned_to' => $validatedData['assigned_to'] ?? null,
            'due_date' => $validatedData['due_date'] ?? null,
            'estimated_hours' => $validatedData['estimated_hours'] ?? null,
            'status' => TASKSTATUS::PENDING->value,
            'progress' => 0,
            'tags' => $validatedData['tags'] ?? [],
            'metadata' => [],
            'result' => null,
        ]);

        // 记录日志
        $this->logger->audit('task_created', $user->id, [
            'task_id' => $task->id,
            'title' => $task->title,
            'type' => $task->type,
            'project_id' => $task->project_id,
            'parent_task_id' => $task->parent_task_id,
        ]);

        // 分发事件
        $this->eventDispatcher->dispatch(new \App\Modules\Task\Events\TaskCreated($task));

        return $task;
    }

    /**
     * 更新任务
     */
    public function update(Task $task, array $data): Task
    {
        // 验证数据
        $validatedData = SimpleValidator::check($data, TaskValidationHelper::getUpdateTaskRules());

        if (empty($validatedData)) {
            $validator = SimpleValidator::make($data, [
                'title' => 'string|min:2|max:255',
                'description' => 'string|max:2000',
                'type' => 'string|in:main,sub,milestone,bug,feature,improvement',
                'priority' => 'string|in:low,medium,high,urgent',
                'status' => 'string|in:pending,in_progress,completed,blocked,cancelled,on_hold',
                'agent_id' => 'integer',
                'assigned_to' => 'string|max:255',
                'due_date' => 'date',
                'estimated_hours' => 'numeric|min:0',
                'actual_hours' => 'numeric|min:0',
                'progress' => 'integer|min:0|max:100',
                'tags' => 'array',
                'result' => 'array',
            ]);
            throw new \InvalidArgumentException('Validation failed: ' . $validator->getFirstError());
        }

        // 验证Agent权限
        if (isset($validatedData['agent_id'])) {
            $agent = Agent::find($validatedData['agent_id']);
            if (!$agent || $agent->user_id !== $task->user_id) {
                throw new \InvalidArgumentException('Invalid agent or insufficient permissions');
            }
        }

        // 处理日期
        if (isset($validatedData['due_date'])) {
            $validatedData['due_date'] = $validatedData['due_date'];
        }

        // 记录原始状态
        $originalStatus = $task->status;
        $originalProgress = $task->progress;
        $originalAgentId = $task->agent_id;

        // 更新任务
        $task->update($validatedData);

        // 如果状态发生变化，记录日志和分发事件
        if (isset($validatedData['status']) && $originalStatus !== $validatedData['status']) {
            $this->logger->audit('task_status_changed', $task->user_id, [
                'task_id' => $task->id,
                'old_status' => $originalStatus,
                'new_status' => $validatedData['status'],
            ]);

            $this->eventDispatcher->dispatch(new \App\Modules\Task\Events\TaskStatusChanged($task, $originalStatus));

            // 如果任务完成，检查父任务是否应该完成
            if ($validatedData['status'] === TASKSTATUS::COMPLETED->value && $task->isSubTask()) {
                $this->checkParentTaskCompletion($task->parentTask);
            }
        }

        // 如果进度发生变化，记录日志
        if (isset($validatedData['progress']) && $originalProgress !== $validatedData['progress']) {
            $this->logger->audit('task_progress_updated', $task->user_id, [
                'task_id' => $task->id,
                'old_progress' => $originalProgress,
                'new_progress' => $validatedData['progress'],
            ]);

            $this->eventDispatcher->dispatch(new \App\Modules\Task\Events\TaskProgressUpdated($task, $originalProgress));
        }

        // 如果Agent发生变化，记录日志和分发事件
        if (isset($validatedData['agent_id']) && $originalAgentId !== $validatedData['agent_id']) {
            $this->logger->audit('task_agent_changed', $task->user_id, [
                'task_id' => $task->id,
                'old_agent_id' => $originalAgentId,
                'new_agent_id' => $validatedData['agent_id'],
            ]);

            $this->eventDispatcher->dispatch(new \App\Modules\Task\Events\TaskAgentChanged($task, $originalAgentId));
        }

        // 记录更新日志
        $this->logger->audit('task_updated', $task->user_id, [
            'task_id' => $task->id,
            'updated_fields' => array_keys($validatedData),
        ]);

        return $task->fresh();
    }

    /**
     * 删除任务
     */
    public function delete(Task $task): bool
    {
        // 检查是否有子任务
        $subTasks = $task->subTasks()->count();
        if ($subTasks > 0) {
            throw new \InvalidArgumentException('Cannot delete task with sub-tasks');
        }

        // 记录日志
        $this->logger->audit('task_deleted', $task->user_id, [
            'task_id' => $task->id,
            'title' => $task->title,
            'type' => $task->type,
        ]);

        // 分发事件
        $this->eventDispatcher->dispatch(new \App\Modules\Task\Events\TaskDeleted($task));

        // 删除任务
        return $task->delete();
    }

    /**
     * 开始任务
     */
    public function startTask(Task $task): Task
    {
        $originalStatus = $task->status;
        $task->start();

        // 记录日志
        $this->logger->audit('task_started', $task->user_id, [
            'task_id' => $task->id,
            'previous_status' => $originalStatus,
        ]);

        // 分发事件
        $this->eventDispatcher->dispatch(new \App\Modules\Task\Events\TaskStarted($task));

        return $task->fresh();
    }

    /**
     * 完成任务
     */
    public function completeTask(Task $task, ?array $result = null): Task
    {
        $originalStatus = $task->status;
        $task->complete();

        // 保存结果
        if ($result) {
            $task->update(['result' => $result]);
        }

        // 记录日志
        $this->logger->audit('task_completed', $task->user_id, [
            'task_id' => $task->id,
            'previous_status' => $originalStatus,
            'has_result' => !empty($result),
        ]);

        // 分发事件
        $this->eventDispatcher->dispatch(new \App\Modules\Task\Events\TaskCompleted($task));

        // 如果是子任务，检查父任务是否应该完成
        if ($task->isSubTask()) {
            $this->checkParentTaskCompletion($task->parentTask);
        }

        return $task->fresh();
    }

    /**
     * 检查父任务是否应该完成
     */
    protected function checkParentTaskCompletion(Task $parentTask): void
    {
        if (!$parentTask || $parentTask->isCompleted()) {
            return;
        }

        $subTasks = $parentTask->subTasks;
        $completedSubTasks = $subTasks->where('status', TASKSTATUS::COMPLETED);

        // 如果所有子任务都完成了，自动完成父任务
        if ($subTasks->count() > 0 && $completedSubTasks->count() === $subTasks->count()) {
            $this->completeTask($parentTask);
        }
    }

    /**
     * 获取用户的任务列表
     */
    public function getUserTasks(User $user, array $filters = []): Collection
    {
        $query = Task::byUser($user->id)->with(['user', 'agent', 'project', 'parentTask']);

        // 应用过滤器
        if (isset($filters['status'])) {
            $query->byStatus($filters['status']);
        }

        if (isset($filters['type'])) {
            $query->byType($filters['type']);
        }

        if (isset($filters['priority'])) {
            $query->byPriority($filters['priority']);
        }

        if (isset($filters['project_id'])) {
            $query->byProject($filters['project_id']);
        }

        if (isset($filters['agent_id'])) {
            $query->byAgent($filters['agent_id']);
        }

        if (isset($filters['main_tasks_only'])) {
            $query->mainTasks();
        }

        if (isset($filters['sub_tasks_only'])) {
            $query->subTasks();
        }

        if (isset($filters['search'])) {
            $query->search($filters['search']);
        }

        if (isset($filters['due_soon'])) {
            $query->dueSoon($filters['due_soon']);
        }

        if (isset($filters['overdue'])) {
            $query->overdue();
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * 获取系统任务统计信息
     */
    public function getSystemStats(): array
    {
        try {
            return [
                'total_tasks' => Task::count(),
                'pending_tasks' => Task::byStatus(TASKSTATUS::PENDING)->count(),
                'in_progress_tasks' => Task::byStatus(TASKSTATUS::IN_PROGRESS)->count(),
                'completed_tasks' => Task::byStatus(TASKSTATUS::COMPLETED)->count(),
                'blocked_tasks' => Task::byStatus(TASKSTATUS::BLOCKED)->count(),
                'main_tasks' => Task::mainTasks()->count(),
                'sub_tasks' => Task::subTasks()->count(),
                'overdue_tasks' => Task::overdue()->count(),
                'due_soon_tasks' => Task::dueSoon()->count(),
                'tasks_by_priority' => [
                    'low' => Task::byPriority(TASKPRIORITY::LOW)->count(),
                    'medium' => Task::byPriority(TASKPRIORITY::MEDIUM)->count(),
                    'high' => Task::byPriority(TASKPRIORITY::HIGH)->count(),
                    'urgent' => Task::byPriority(TASKPRIORITY::URGENT)->count(),
                ],
                'table_exists' => true,
            ];
        } catch (\Exception $e) {
            return [
                'total_tasks' => 0,
                'pending_tasks' => 0,
                'in_progress_tasks' => 0,
                'completed_tasks' => 0,
                'blocked_tasks' => 0,
                'main_tasks' => 0,
                'sub_tasks' => 0,
                'overdue_tasks' => 0,
                'due_soon_tasks' => 0,
                'tasks_by_priority' => [
                    'low' => 0,
                    'medium' => 0,
                    'high' => 0,
                    'urgent' => 0,
                ],
                'table_exists' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
