<?php

namespace App\Modules\Task\Workflows\Rules;

use App\Modules\Task\Models\Task;
use App\Modules\Task\Enums\TASKSTATUS;

/**
 * 子任务完成规则
 * 
 * 确保主任务只有在所有子任务完成后才能完成
 */
class SubTaskCompletionRule extends AbstractWorkflowRule
{
    protected int $priority = 10; // 高优先级

    /**
     * 获取规则名称
     */
    public function getName(): string
    {
        return 'SubTaskCompletionRule';
    }

    /**
     * 获取规则描述
     */
    public function getDescription(): string
    {
        return '主任务只有在所有子任务完成后才能完成';
    }

    /**
     * 检查规则是否适用于当前转换
     */
    public function canApply(Task $task, TASKSTATUS $fromStatus, TASKSTATUS $toStatus, array $context = []): bool
    {
        // 只对主任务转换为完成状态时适用
        return $task->isMainTask() && $toStatus === TASKSTATUS::COMPLETED;
    }

    /**
     * 验证状态转换是否符合规则
     */
    public function validate(Task $task, TASKSTATUS $fromStatus, TASKSTATUS $toStatus, array $context = []): bool
    {
        $this->clearErrorMessage();

        // 检查是否有未完成的子任务
        $incompleteSubTasks = $task->subTasks()
            ->whereNotIn('status', [TASKSTATUS::COMPLETED->value, TASKSTATUS::CANCELLED->value])
            ->count();

        if ($incompleteSubTasks > 0) {
            $this->setErrorMessage("任务还有 {$incompleteSubTasks} 个未完成的子任务，无法完成主任务");
            return false;
        }

        return true;
    }

    /**
     * 在状态转换后执行的操作
     */
    public function afterTransition(Task $task, TASKSTATUS $fromStatus, TASKSTATUS $toStatus, array $context = []): void
    {
        parent::afterTransition($task, $fromStatus, $toStatus, $context);

        // 记录主任务完成时的子任务统计
        $totalSubTasks = $task->subTasks()->count();
        $completedSubTasks = $task->subTasks()->where('status', TASKSTATUS::COMPLETED->value)->count();
        $cancelledSubTasks = $task->subTasks()->where('status', TASKSTATUS::CANCELLED->value)->count();

        $this->logRuleExecution('main_task_completed', $task, [
            'total_sub_tasks' => $totalSubTasks,
            'completed_sub_tasks' => $completedSubTasks,
            'cancelled_sub_tasks' => $cancelledSubTasks,
        ]);
    }
}
