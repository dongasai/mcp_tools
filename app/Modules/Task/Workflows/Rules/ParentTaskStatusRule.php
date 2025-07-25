<?php

namespace App\Modules\Task\Workflows\Rules;

use App\Modules\Task\Models\Task;
use App\Modules\Task\Enums\TASKSTATUS;

/**
 * 父任务状态规则
 * 
 * 确保子任务的状态转换符合父任务的状态约束
 */
class ParentTaskStatusRule extends AbstractWorkflowRule
{
    protected int $priority = 20; // 高优先级

    /**
     * 获取规则名称
     */
    public function getName(): string
    {
        return 'ParentTaskStatusRule';
    }

    /**
     * 获取规则描述
     */
    public function getDescription(): string
    {
        return '子任务的状态转换必须符合父任务的状态约束';
    }

    /**
     * 检查规则是否适用于当前转换
     */
    public function canApply(Task $task, TASKSTATUS $fromStatus, TASKSTATUS $toStatus, array $context = []): bool
    {
        // 只对有父任务的子任务适用
        return $task->isSubTask() && $task->parentTask !== null;
    }

    /**
     * 验证状态转换是否符合规则
     */
    public function validate(Task $task, TASKSTATUS $fromStatus, TASKSTATUS $toStatus, array $context = []): bool
    {
        $this->clearErrorMessage();

        $parentTask = $task->parentTask;
        
        // 如果父任务已完成或已取消，子任务不能开始或进行中
        if (in_array($parentTask->status, [TASKSTATUS::COMPLETED, TASKSTATUS::CANCELLED])) {
            if (in_array($toStatus, [TASKSTATUS::PENDING, TASKSTATUS::IN_PROGRESS])) {
                $this->setErrorMessage("父任务已{$parentTask->status->label()}，子任务不能转换为{$toStatus->label()}状态");
                return false;
            }
        }

        // 如果父任务被阻塞，子任务不能开始
        if ($parentTask->status === TASKSTATUS::BLOCKED && $toStatus === TASKSTATUS::IN_PROGRESS) {
            $this->setErrorMessage("父任务被阻塞，子任务不能开始执行");
            return false;
        }

        // 如果父任务暂停，子任务不能开始
        if ($parentTask->status === TASKSTATUS::ON_HOLD && $toStatus === TASKSTATUS::IN_PROGRESS) {
            $this->setErrorMessage("父任务已暂停，子任务不能开始执行");
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

        // 如果子任务完成，检查是否需要自动完成父任务
        if ($toStatus === TASKSTATUS::COMPLETED && config('task.automation.auto_complete_parent_task', true)) {
            $this->checkParentTaskAutoCompletion($task);
        }
    }

    /**
     * 检查父任务自动完成
     */
    private function checkParentTaskAutoCompletion(Task $task): void
    {
        $parentTask = $task->parentTask;
        
        // 检查是否所有子任务都已完成
        if ($parentTask->areAllSubTasksCompleted()) {
            $this->logRuleExecution('auto_complete_parent_task', $task, [
                'parent_task_id' => $parentTask->id,
                'parent_task_title' => $parentTask->title,
            ]);
            
            // 注意：这里不直接更新状态，而是通过事件系统处理
            // 避免在规则中直接修改数据库状态
        }
    }
}
