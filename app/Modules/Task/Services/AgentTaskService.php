<?php

namespace App\Modules\Task\Services;

use App\Modules\Task\Models\AgentTask;
use App\Modules\Task\Models\Task;
use App\Modules\Agent\Models\Agent;
use App\Modules\Core\Contracts\LogInterface;
use App\Modules\Core\Contracts\EventInterface;
use App\Modules\Core\Validators\SimpleValidator;
use App\Modules\Task\Enums\TASKPRIORITY;
use Illuminate\Support\Collection;

class AgentTaskService
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
     * 创建Agent任务
     */
    public function create(Agent $agent, array $data): AgentTask
    {
        // 验证数据
        $validator = SimpleValidator::make($data, [
            'main_task_id' => 'integer|nullable',
            'title' => 'required|string|min:2|max:255',
            'description' => 'string|max:2000',
            'type' => 'required|string|in:code_analysis,file_operation,api_call,data_processing,github_operation,validation',
            'priority' => 'string|in:low,medium,high,urgent',
            'execution_data' => 'array',
            'estimated_duration' => 'integer|min:0',
            'max_retries' => 'integer|min:0|max:10',
            'metadata' => 'array',
        ]);

        if ($validator->fails()) {
            throw new \InvalidArgumentException('验证失败: ' . implode(', ', $validator->errors()));
        }

        $validatedData = $validator->validated();

        // 验证主任务权限（如果指定了主任务）
        if (isset($validatedData['main_task_id'])) {
            $mainTask = Task::find($validatedData['main_task_id']);
            if (!$mainTask) {
                throw new \InvalidArgumentException('指定的主任务不存在');
            }
        }

        // 设置默认值
        $taskData = array_merge([
            'agent_id' => $agent->id,
            'status' => 'pending',
            'priority' => TASKPRIORITY::MEDIUM,
            'retry_count' => 0,
            'max_retries' => 3,
        ], $validatedData);

        // 创建Agent任务
        $agentTask = AgentTask::create($taskData);

        // 记录日志
        $this->logger->audit('agent_task_created', $agent->user_id, [
            'agent_task_id' => $agentTask->id,
            'agent_id' => $agent->id,
            'main_task_id' => $agentTask->main_task_id,
            'task_data' => $taskData,
        ]);

        return $agentTask;
    }

    /**
     * 更新Agent任务
     */
    public function update(AgentTask $agentTask, array $data): AgentTask
    {
        // 验证数据
        $validator = SimpleValidator::make($data, [
            'title' => 'string|min:2|max:255',
            'description' => 'string|max:2000',
            'priority' => 'string|in:low,medium,high,urgent',
            'execution_data' => 'array',
            'estimated_duration' => 'integer|min:0',
            'max_retries' => 'integer|min:0|max:10',
            'metadata' => 'array',
        ]);

        if ($validator->fails()) {
            throw new \InvalidArgumentException('验证失败: ' . implode(', ', $validator->errors()));
        }

        $validatedData = $validator->validated();

        // 更新任务
        $agentTask->update($validatedData);

        // 记录日志
        $this->logger->audit('agent_task_updated', $agentTask->agent->user_id, [
            'agent_task_id' => $agentTask->id,
            'updated_data' => $validatedData,
        ]);

        return $agentTask->fresh();
    }

    /**
     * 删除Agent任务
     */
    public function delete(AgentTask $agentTask): bool
    {
        $taskId = $agentTask->id;
        $userId = $agentTask->agent->user_id;

        // 删除任务
        $deleted = $agentTask->delete();

        if ($deleted) {
            // 记录日志
            $this->logger->audit('agent_task_deleted', $userId, [
                'agent_task_id' => $taskId,
            ]);
        }

        return $deleted;
    }

    /**
     * 开始执行Agent任务
     */
    public function startTask(AgentTask $agentTask): AgentTask
    {
        $originalStatus = $agentTask->status;

        // 开始执行
        $agentTask->start();

        // 记录日志
        $this->logger->audit('agent_task_started', $agentTask->agent->user_id, [
            'agent_task_id' => $agentTask->id,
            'previous_status' => $originalStatus,
        ]);

        return $agentTask->fresh();
    }

    /**
     * 完成Agent任务
     */
    public function completeTask(AgentTask $agentTask, array $resultData = []): AgentTask
    {
        $originalStatus = $agentTask->status;

        // 完成任务
        $agentTask->complete($resultData);

        // 记录日志
        $this->logger->audit('agent_task_completed', $agentTask->agent->user_id, [
            'agent_task_id' => $agentTask->id,
            'previous_status' => $originalStatus,
            'has_result' => !empty($resultData),
        ]);

        // 检查关联的主任务是否可以完成
        if ($agentTask->main_task_id) {
            $this->checkMainTaskCompletion($agentTask->mainTask);
        }

        return $agentTask->fresh();
    }

    /**
     * 标记Agent任务失败
     */
    public function failTask(AgentTask $agentTask, string $errorMessage = ''): AgentTask
    {
        $originalStatus = $agentTask->status;

        // 标记失败
        $agentTask->fail($errorMessage);

        // 记录日志
        $this->logger->audit('agent_task_failed', $agentTask->agent->user_id, [
            'agent_task_id' => $agentTask->id,
            'previous_status' => $originalStatus,
            'error_message' => $errorMessage,
        ]);

        // 检查是否可以重试
        if ($agentTask->canRetry()) {
            $this->retryTask($agentTask);
        }

        return $agentTask->fresh();
    }

    /**
     * 重试Agent任务
     */
    public function retryTask(AgentTask $agentTask): AgentTask
    {
        if (!$agentTask->canRetry()) {
            throw new \InvalidArgumentException('任务无法重试');
        }

        // 重试任务
        $agentTask->retry();

        // 记录日志
        $this->logger->audit('agent_task_retried', $agentTask->agent->user_id, [
            'agent_task_id' => $agentTask->id,
            'retry_count' => $agentTask->retry_count,
        ]);

        return $agentTask->fresh();
    }

    /**
     * 取消Agent任务
     */
    public function cancelTask(AgentTask $agentTask): AgentTask
    {
        $originalStatus = $agentTask->status;

        // 取消任务
        $agentTask->cancel();

        // 记录日志
        $this->logger->audit('agent_task_cancelled', $agentTask->agent->user_id, [
            'agent_task_id' => $agentTask->id,
            'previous_status' => $originalStatus,
        ]);

        return $agentTask->fresh();
    }

    /**
     * 获取Agent的任务列表
     */
    public function getAgentTasks(Agent $agent, array $filters = []): Collection
    {
        $query = AgentTask::byAgent($agent->id)->with(['agent', 'mainTask']);

        // 应用过滤器
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['type'])) {
            $query->byType($filters['type']);
        }

        if (isset($filters['main_task_id'])) {
            $query->byMainTask($filters['main_task_id']);
        }

        if (isset($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * 获取主任务的Agent任务列表
     */
    public function getMainTaskAgentTasks(Task $mainTask): Collection
    {
        return AgentTask::byMainTask($mainTask->id)
            ->with(['agent'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * 检查主任务是否可以完成
     */
    protected function checkMainTaskCompletion(Task $mainTask): void
    {
        $agentTasks = $this->getMainTaskAgentTasks($mainTask);
        
        // 检查是否所有Agent任务都已完成
        $allCompleted = $agentTasks->every(function ($agentTask) {
            return $agentTask->isCompleted();
        });

        if ($allCompleted && $agentTasks->isNotEmpty()) {
            // 可以考虑自动完成主任务，但这里只记录日志
            $this->logger->audit('main_task_ready_for_completion', $mainTask->user_id, [
                'main_task_id' => $mainTask->id,
                'completed_agent_tasks_count' => $agentTasks->count(),
            ]);
        }
    }

    /**
     * 获取Agent任务统计信息
     */
    public function getAgentTaskStats(AgentTask $agentTask): array
    {
        return [
            'id' => $agentTask->id,
            'title' => $agentTask->title,
            'status' => $agentTask->status,
            'type' => $agentTask->type,
            'progress_description' => $agentTask->getProgressDescription(),
            'execution_duration' => $agentTask->getExecutionDuration(),
            'retry_count' => $agentTask->retry_count,
            'max_retries' => $agentTask->max_retries,
            'can_retry' => $agentTask->canRetry(),
            'is_completed' => $agentTask->isCompleted(),
            'is_failed' => $agentTask->isFailed(),
            'is_running' => $agentTask->isRunning(),
        ];
    }
}
