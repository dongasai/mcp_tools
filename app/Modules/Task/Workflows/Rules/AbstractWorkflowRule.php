<?php

namespace App\Modules\Task\Workflows\Rules;

use App\Modules\Task\Models\Task;
use App\Modules\Task\Enums\TASKSTATUS;
use Illuminate\Support\Facades\Log;

/**
 * 工作流规则抽象基类
 * 
 * 提供工作流规则的基础实现，简化具体规则的开发
 */
abstract class AbstractWorkflowRule implements WorkflowRuleInterface
{
    /**
     * 错误信息
     */
    protected string $errorMessage = '';

    /**
     * 规则优先级
     */
    protected int $priority = 100;

    /**
     * 检查规则是否适用于当前转换
     * 
     * 默认实现：所有转换都适用，子类可以重写
     */
    public function canApply(Task $task, TASKSTATUS $fromStatus, TASKSTATUS $toStatus, array $context = []): bool
    {
        return true;
    }

    /**
     * 获取验证失败的错误信息
     */
    public function getErrorMessage(): string
    {
        return $this->errorMessage;
    }

    /**
     * 在状态转换前执行的操作
     * 
     * 默认实现：记录日志，子类可以重写
     */
    public function beforeTransition(Task $task, TASKSTATUS $fromStatus, TASKSTATUS $toStatus, array $context = []): void
    {
        Log::debug("Workflow rule {$this->getName()} before transition", [
            'task_id' => $task->id,
            'from_status' => $fromStatus->value,
            'to_status' => $toStatus->value,
            'rule' => $this->getName(),
        ]);
    }

    /**
     * 在状态转换后执行的操作
     * 
     * 默认实现：记录日志，子类可以重写
     */
    public function afterTransition(Task $task, TASKSTATUS $fromStatus, TASKSTATUS $toStatus, array $context = []): void
    {
        Log::debug("Workflow rule {$this->getName()} after transition", [
            'task_id' => $task->id,
            'from_status' => $fromStatus->value,
            'to_status' => $toStatus->value,
            'rule' => $this->getName(),
        ]);
    }

    /**
     * 获取规则优先级
     */
    public function getPriority(): int
    {
        return $this->priority;
    }

    /**
     * 设置错误信息
     */
    protected function setErrorMessage(string $message): void
    {
        $this->errorMessage = $message;
    }

    /**
     * 清除错误信息
     */
    protected function clearErrorMessage(): void
    {
        $this->errorMessage = '';
    }

    /**
     * 记录规则执行日志
     */
    protected function logRuleExecution(string $action, Task $task, array $data = []): void
    {
        Log::info("Workflow rule execution: {$this->getName()}", array_merge([
            'action' => $action,
            'task_id' => $task->id,
            'rule' => $this->getName(),
        ], $data));
    }
}
