<?php

namespace Modules\Task\Services;

use Modules\Task\Models\Task;
use App\Modules\User\Models\User;
use App\Modules\Agent\Models\Agent;
use App\Modules\Project\Models\Project;
use App\Modules\Core\Contracts\LogInterface;
use App\Modules\Core\Contracts\EventInterface;
use App\Modules\Core\Validators\SimpleValidator;
use Modules\Task\Enums\TASKSTATUS;
use Modules\Task\Enums\TASKTYPE;
use Modules\Task\Enums\TASKPRIORITY;
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
        $validator = SimpleValidator::make($data, [
            'title' => 'required|string|min:2|max:255',
            'description' => 'string|max:2000',
            'type' => 'string|in:main,sub,milestone,bug,feature,improvement',
            'priority' => 'string|in:low,medium,high,urgent',
            'project_id' => 'integer',
            'agent_id' => 'integer',
            'parent_task_id' => 'integer',
            'assigned_to' => 'integer',
            'due_date' => 'date',
            'estimated_hours' => 'numeric|min:0',
            'tags' => 'array',
            'metadata' => 'array',
        ]);

        if ($validator->fails()) {
            throw new \InvalidArgumentException('验证失败: ' . implode(', ', $validator->errors()));
        }

        $validatedData = $validator->validated();

        // 设置默认值
        $taskData = array_merge([
            'user_id' => $user->id,
            'status' => TASKSTATUS::PENDING,
            'type' => TASKTYPE::MAIN,
            'priority' => TASKPRIORITY::MEDIUM,
            'progress' => 0,
        ], $validatedData);

        // 创建任务
        $task = Task::create($taskData);

        // 记录日志
        $this->logger->audit('task_created', $user->id, [
            'task_id' => $task->id,
            'task_data' => $taskData,
        ]);

        return $task;
    }

    /**
     * 更新任务
     */
    public function update(Task $task, array $data): Task
    {
        // 验证数据
        $validator = SimpleValidator::make($data, [
            'title' => 'string|min:2|max:255',
            'description' => 'string|max:2000',
            'priority' => 'string|in:low,medium,high,urgent',
            'assigned_to' => 'integer',
            'due_date' => 'date',
            'estimated_hours' => 'numeric|min:0',
            'tags' => 'array',
            'metadata' => 'array',
        ]);

        if ($validator->fails()) {
            throw new \InvalidArgumentException('验证失败: ' . implode(', ', $validator->errors()));
        }

        $validatedData = $validator->validated();

        // 更新任务
        $task->update($validatedData);

        // 记录日志
        $this->logger->audit('task_updated', $task->user_id, [
            'task_id' => $task->id,
            'updated_data' => $validatedData,
        ]);

        return $task->fresh();
    }

    /**
     * 删除任务
     */
    public function delete(Task $task): bool
    {
        $taskId = $task->id;
        $userId = $task->user_id;

        // 删除任务
        $deleted = $task->delete();

        if ($deleted) {
            // 记录日志
            $this->logger->audit('task_deleted', $userId, [
                'task_id' => $taskId,
            ]);
        }

        return $deleted;
    }

    /**
     * 开始任务
     */
    public function startTask(Task $task): Task
    {
        $originalStatus = $task->status;

        // 简单的状态转换
        $task->start();

        // 记录日志
        $this->logger->audit('task_started', $task->user_id, [
            'task_id' => $task->id,
            'previous_status' => $originalStatus,
        ]);

        return $task->fresh();
    }

    /**
     * 完成任务
     */
    public function completeTask(Task $task, ?array $result = null): Task
    {
        $originalStatus = $task->status;

        // 简单的状态转换
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

        return $task->fresh();
    }
}