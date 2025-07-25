<?php

namespace App\Modules\Task\Workflows\Rules;

use App\Modules\Task\Models\Task;
use App\Modules\Task\Enums\TASKSTATUS;

/**
 * 基础转换规则
 * 
 * 验证基本的状态转换逻辑，基于TASKSTATUS枚举的canTransitionTo方法
 */
class BasicTransitionRule extends AbstractWorkflowRule
{
    protected int $priority = 1; // 最高优先级，基础验证

    /**
     * 获取规则名称
     */
    public function getName(): string
    {
        return 'BasicTransitionRule';
    }

    /**
     * 获取规则描述
     */
    public function getDescription(): string
    {
        return '验证基本的状态转换逻辑，确保状态转换符合业务规则';
    }

    /**
     * 检查规则是否适用于当前转换
     */
    public function canApply(Task $task, TASKSTATUS $fromStatus, TASKSTATUS $toStatus, array $context = []): bool
    {
        // 对所有状态转换都适用
        return true;
    }

    /**
     * 验证状态转换是否符合规则
     */
    public function validate(Task $task, TASKSTATUS $fromStatus, TASKSTATUS $toStatus, array $context = []): bool
    {
        $this->clearErrorMessage();

        // 使用枚举的canTransitionTo方法进行基础验证
        if (!$fromStatus->canTransitionTo($toStatus)) {
            $this->setErrorMessage("不能从{$fromStatus->label()}状态转换到{$toStatus->label()}状态");
            return false;
        }

        // 检查是否是无效的转换（相同状态）
        if ($fromStatus === $toStatus) {
            $this->setErrorMessage("任务已经是{$toStatus->label()}状态");
            return false;
        }

        return true;
    }

    /**
     * 在状态转换前执行的操作
     */
    public function beforeTransition(Task $task, TASKSTATUS $fromStatus, TASKSTATUS $toStatus, array $context = []): void
    {
        parent::beforeTransition($task, $fromStatus, $toStatus, $context);

        // 记录状态转换的基础信息
        $this->logRuleExecution('basic_transition_validation', $task, [
            'from_status' => $fromStatus->value,
            'to_status' => $toStatus->value,
            'task_type' => $task->type->value,
            'has_parent' => $task->isSubTask(),
            'has_children' => $task->isMainTask() && $task->subTasks()->count() > 0,
        ]);
    }

    /**
     * 在状态转换后执行的操作
     */
    public function afterTransition(Task $task, TASKSTATUS $fromStatus, TASKSTATUS $toStatus, array $context = []): void
    {
        parent::afterTransition($task, $fromStatus, $toStatus, $context);

        // 根据目标状态执行相应的后处理
        match ($toStatus) {
            TASKSTATUS::IN_PROGRESS => $this->handleTaskStarted($task),
            TASKSTATUS::COMPLETED => $this->handleTaskCompleted($task),
            TASKSTATUS::BLOCKED => $this->handleTaskBlocked($task),
            TASKSTATUS::CANCELLED => $this->handleTaskCancelled($task),
            TASKSTATUS::ON_HOLD => $this->handleTaskOnHold($task),
            default => null,
        };
    }

    /**
     * 处理任务开始
     */
    private function handleTaskStarted(Task $task): void
    {
        // 自动设置开始时间（如果有相关字段）
        // 这里可以扩展更多的开始逻辑
        $this->logRuleExecution('task_started', $task);
    }

    /**
     * 处理任务完成
     */
    private function handleTaskCompleted(Task $task): void
    {
        // 自动设置进度为100%
        if ($task->progress < 100) {
            $task->update(['progress' => 100]);
        }
        
        $this->logRuleExecution('task_completed', $task, [
            'progress_updated' => $task->progress,
        ]);
    }

    /**
     * 处理任务阻塞
     */
    private function handleTaskBlocked(Task $task): void
    {
        $this->logRuleExecution('task_blocked', $task);
    }

    /**
     * 处理任务取消
     */
    private function handleTaskCancelled(Task $task): void
    {
        $this->logRuleExecution('task_cancelled', $task);
    }

    /**
     * 处理任务暂停
     */
    private function handleTaskOnHold(Task $task): void
    {
        $this->logRuleExecution('task_on_hold', $task);
    }
}
