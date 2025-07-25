<?php

namespace App\Modules\Task\Workflows;

use App\Modules\Task\Models\Task;
use App\Modules\Task\Enums\TASKSTATUS;
use App\Modules\Task\Workflows\Rules\WorkflowRuleInterface;
use App\Modules\Task\Workflows\Rules\BasicTransitionRule;
use App\Modules\Task\Workflows\Rules\SubTaskCompletionRule;
use App\Modules\Task\Workflows\Rules\ParentTaskStatusRule;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

/**
 * 任务状态机
 * 
 * 管理任务状态转换的核心类，集成工作流规则和业务逻辑
 */
class TaskStateMachine
{
    /**
     * 任务实例
     */
    private Task $task;

    /**
     * 工作流规则集合
     */
    private Collection $rules;

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
        $this->rules = collect();
        
        $this->initializeDefaultRules();
    }

    /**
     * 初始化默认规则
     */
    private function initializeDefaultRules(): void
    {
        $this->addRule(new BasicTransitionRule());
        $this->addRule(new SubTaskCompletionRule());
        $this->addRule(new ParentTaskStatusRule());
    }

    /**
     * 添加工作流规则
     */
    public function addRule(WorkflowRuleInterface $rule): self
    {
        $this->rules->push($rule);
        
        // 按优先级排序
        $this->rules = $this->rules->sortBy(fn($rule) => $rule->getPriority());
        
        return $this;
    }

    /**
     * 移除工作流规则
     */
    public function removeRule(string $ruleName): self
    {
        $this->rules = $this->rules->reject(fn($rule) => $rule->getName() === $ruleName);
        
        return $this;
    }

    /**
     * 检查是否可以转换到指定状态
     */
    public function canTransition(TASKSTATUS $toStatus, array $context = []): bool
    {
        $this->clearErrors();
        $mergedContext = array_merge($this->context, $context);
        $fromStatus = $this->task->status;

        // 应用所有适用的规则进行验证
        foreach ($this->getApplicableRules($fromStatus, $toStatus, $mergedContext) as $rule) {
            if (!$rule->validate($this->task, $fromStatus, $toStatus, $mergedContext)) {
                $this->addError($rule->getName(), $rule->getErrorMessage());
            }
        }

        return empty($this->errors);
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
            // 执行转换前的规则操作
            $this->executeBeforeTransition($fromStatus, $toStatus, $mergedContext);

            // 执行实际的状态转换
            $this->task->update(['status' => $toStatus->value]);

            // 执行转换后的规则操作
            $this->executeAfterTransition($fromStatus, $toStatus, $mergedContext);

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
        $mergedContext = array_merge($this->context, $context);
        $fromStatus = $this->task->status;

        $result = [
            'valid' => true,
            'errors' => [],
            'warnings' => [],
            'applicable_rules' => [],
        ];

        foreach ($this->getApplicableRules($fromStatus, $toStatus, $mergedContext) as $rule) {
            $result['applicable_rules'][] = [
                'name' => $rule->getName(),
                'description' => $rule->getDescription(),
                'priority' => $rule->getPriority(),
            ];

            if (!$rule->validate($this->task, $fromStatus, $toStatus, $mergedContext)) {
                $result['valid'] = false;
                $result['errors'][] = [
                    'rule' => $rule->getName(),
                    'message' => $rule->getErrorMessage(),
                ];
            }
        }

        return $result;
    }

    /**
     * 获取适用的规则
     */
    private function getApplicableRules(TASKSTATUS $fromStatus, TASKSTATUS $toStatus, array $context): Collection
    {
        return $this->rules->filter(function ($rule) use ($fromStatus, $toStatus, $context) {
            return $rule->canApply($this->task, $fromStatus, $toStatus, $context);
        });
    }

    /**
     * 执行转换前的规则操作
     */
    private function executeBeforeTransition(TASKSTATUS $fromStatus, TASKSTATUS $toStatus, array $context): void
    {
        foreach ($this->getApplicableRules($fromStatus, $toStatus, $context) as $rule) {
            $rule->beforeTransition($this->task, $fromStatus, $toStatus, $context);
        }
    }

    /**
     * 执行转换后的规则操作
     */
    private function executeAfterTransition(TASKSTATUS $fromStatus, TASKSTATUS $toStatus, array $context): void
    {
        foreach ($this->getApplicableRules($fromStatus, $toStatus, $context) as $rule) {
            $rule->afterTransition($this->task, $fromStatus, $toStatus, $context);
        }
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
