<?php

namespace App\Modules\Task\Workflows;

use App\Modules\Task\Models\Task;
use App\Modules\Task\Enums\TASKSTATUS;
use Illuminate\Support\Facades\Log;

/**
 * 任务状态机
 *
 * 管理任务状态转换的核心类，简化的状态转换逻辑
 */
class TaskStateMachine
{
    /**
     * 任务实例
     */
    private Task $task;

    /**
     * 转换上下文
     */
    private array $context;

    /**
     * 错误信息集合
     */
    private array $errors = [];

    /**
     * 构造函数
     */
    public function __construct(Task $task, array $context = [])
    {
        $this->task = $task;
        $this->context = $context;
    }

    /**
     * 检查是否可以转换到指定状态
     */
    public function canTransition(TASKSTATUS $toStatus, array $context = []): bool
    {
        $this->clearErrors();
        $fromStatus = $this->task->status;

        // 基础状态转换验证
        if (!$fromStatus->canTransitionTo($toStatus)) {
            $this->addError('basic_transition', "不能从{$fromStatus->label()}状态转换到{$toStatus->label()}状态");
            return false;
        }

        // 检查是否是无效的转换（相同状态）
        if ($fromStatus === $toStatus) {
            $this->addError('same_status', "任务已经是{$toStatus->label()}状态");
            return false;
        }

        // 子任务完成规则：主任务只有在所有子任务完成后才能完成
        if ($this->task->isMainTask() && $toStatus === TASKSTATUS::COMPLETED) {
            $incompleteSubTasks = $this->task->subTasks()
                ->whereNotIn('status', [TASKSTATUS::COMPLETED->value, TASKSTATUS::CANCELLED->value])
                ->count();

            if ($incompleteSubTasks > 0) {
                $this->addError('sub_task_completion', "任务还有 {$incompleteSubTasks} 个未完成的子任务，无法完成主任务");
                return false;
            }
        }

        // 父任务状态规则：子任务的状态转换必须符合父任务的状态约束
        if ($this->task->isSubTask() && $this->task->parentTask !== null) {
            $parentTask = $this->task->parentTask;

            // 如果父任务已完成或已取消，子任务不能开始或进行中
            if (in_array($parentTask->status, [TASKSTATUS::COMPLETED, TASKSTATUS::CANCELLED])) {
                if (in_array($toStatus, [TASKSTATUS::PENDING, TASKSTATUS::IN_PROGRESS])) {
                    $this->addError('parent_task_status', "父任务已{$parentTask->status->label()}，子任务不能转换为{$toStatus->label()}状态");
                    return false;
                }
            }

            // 如果父任务被阻塞，子任务不能开始
            if ($parentTask->status === TASKSTATUS::BLOCKED && $toStatus === TASKSTATUS::IN_PROGRESS) {
                $this->addError('parent_task_blocked', "父任务被阻塞，子任务不能开始执行");
                return false;
            }

            // 如果父任务暂停，子任务不能开始
            if ($parentTask->status === TASKSTATUS::ON_HOLD && $toStatus === TASKSTATUS::IN_PROGRESS) {
                $this->addError('parent_task_on_hold', "父任务已暂停，子任务不能开始执行");
                return false;
            }
        }

        return true;
    }

    /**
     * 执行状态转换
     */
    public function transition(TASKSTATUS $toStatus, array $context = []): bool
    {
        $mergedContext = array_merge($this->context, $context);
        $fromStatus = $this->task->status;

        // 验证转换是否可行
        if (!$this->canTransition($toStatus, $context)) {
            Log::warning('Task state transition failed validation', [
                'task_id' => $this->task->id,
                'from_status' => $fromStatus->value,
                'to_status' => $toStatus->value,
                'errors' => $this->errors,
            ]);
            return false;
        }

        try {
            // 执行实际的状态转换
            $this->task->update(['status' => $toStatus->value]);

            // 执行转换后的处理
            $this->handleAfterTransition($fromStatus, $toStatus, $mergedContext);

            Log::info('Task state transition completed', [
                'task_id' => $this->task->id,
                'from_status' => $fromStatus->value,
                'to_status' => $toStatus->value,
                'context' => $mergedContext,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Task state transition failed', [
                'task_id' => $this->task->id,
                'from_status' => $fromStatus->value,
                'to_status' => $toStatus->value,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // 回滚状态（如果需要）
            $this->rollbackTransition($fromStatus);

            return false;
        }
    }

    /**
     * 获取可用的状态转换
     */
    public function getAvailableTransitions(): array
    {
        $currentStatus = $this->task->status;
        $availableTransitions = [];

        foreach (TASKSTATUS::cases() as $status) {
            if ($status !== $currentStatus && $this->canTransition($status)) {
                $availableTransitions[] = [
                    'status' => $status,
                    'label' => $status->label(),
                    'color' => $status->color(),
                    'icon' => $status->icon(),
                ];
            }
        }

        return $availableTransitions;
    }

    /**
     * 验证状态转换并返回详细信息
     */
    public function validateTransition(TASKSTATUS $toStatus, array $context = []): array
    {
        $this->clearErrors();
        $fromStatus = $this->task->status;

        $result = [
            'valid' => true,
            'errors' => [],
            'warnings' => [],
        ];

        $isValid = $this->canTransition($toStatus, $context);
        $result['valid'] = $isValid;

        if (!$isValid) {
            foreach ($this->errors as $key => $message) {
                $result['errors'][] = [
                    'rule' => $key,
                    'message' => $message,
                ];
            }
        }

        return $result;
    }

    /**
     * 执行转换后的处理
     */
    private function handleAfterTransition(TASKSTATUS $fromStatus, TASKSTATUS $toStatus, array $context): void
    {
        // 根据目标状态执行相应的后处理
        match ($toStatus) {
            TASKSTATUS::IN_PROGRESS => $this->handleTaskStarted(),
            TASKSTATUS::COMPLETED => $this->handleTaskCompleted(),
            TASKSTATUS::BLOCKED => $this->handleTaskBlocked(),
            TASKSTATUS::CANCELLED => $this->handleTaskCancelled(),
            TASKSTATUS::ON_HOLD => $this->handleTaskOnHold(),
            default => null,
        };
    }

    /**
     * 处理任务开始
     */
    private function handleTaskStarted(): void
    {
        Log::debug('Task started', ['task_id' => $this->task->id]);
    }

    /**
     * 处理任务完成
     */
    private function handleTaskCompleted(): void
    {
        // 自动设置进度为100%
        if ($this->task->progress < 100) {
            $this->task->update(['progress' => 100]);
        }

        Log::debug('Task completed', [
            'task_id' => $this->task->id,
            'progress_updated' => $this->task->progress,
        ]);
    }

    /**
     * 处理任务阻塞
     */
    private function handleTaskBlocked(): void
    {
        Log::debug('Task blocked', ['task_id' => $this->task->id]);
    }

    /**
     * 处理任务取消
     */
    private function handleTaskCancelled(): void
    {
        Log::debug('Task cancelled', ['task_id' => $this->task->id]);
    }

    /**
     * 处理任务暂停
     */
    private function handleTaskOnHold(): void
    {
        Log::debug('Task on hold', ['task_id' => $this->task->id]);
    }



    /**
     * 回滚状态转换
     */
    private function rollbackTransition(TASKSTATUS $originalStatus): void
    {
        try {
            $this->task->update(['status' => $originalStatus->value]);
            Log::info('Task state transition rolled back', [
                'task_id' => $this->task->id,
                'rolled_back_to' => $originalStatus->value,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to rollback task state transition', [
                'task_id' => $this->task->id,
                'original_status' => $originalStatus->value,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 添加错误信息
     */
    private function addError(string $rule, string $message): void
    {
        $this->errors[$rule] = $message;
    }

    /**
     * 清除错误信息
     */
    private function clearErrors(): void
    {
        $this->errors = [];
    }

    /**
     * 获取错误信息
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * 获取第一个错误信息
     */
    public function getFirstError(): ?string
    {
        return empty($this->errors) ? null : array_values($this->errors)[0];
    }

    /**
     * 获取任务实例
     */
    public function getTask(): Task
    {
        return $this->task;
    }

    /**
     * 获取上下文信息
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * 设置上下文信息
     */
    public function setContext(array $context): self
    {
        $this->context = $context;
        return $this;
    }
}
