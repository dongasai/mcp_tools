<?php

namespace App\Modules\Task\Services;

use App\Modules\Task\Models\Task;
use App\Modules\Task\Enums\TASKSTATUS;
use App\Modules\Task\Workflows\TaskStateMachine;
use Illuminate\Support\Facades\Log;

/**
 * 任务工作流服务
 * 
 * 提供任务工作流管理的高级接口，封装状态机的使用
 */
class TaskWorkflowService
{
    /**
     * 创建任务状态机实例
     */
    public function createStateMachine(Task $task, array $context = []): TaskStateMachine
    {
        return new TaskStateMachine($task, $context);
    }

    /**
     * 检查任务是否可以转换到指定状态
     */
    public function canTransition(Task $task, TASKSTATUS $toStatus, array $context = []): bool
    {
        $stateMachine = $this->createStateMachine($task, $context);
        return $stateMachine->canTransition($toStatus);
    }

    /**
     * 执行任务状态转换
     */
    public function transition(Task $task, TASKSTATUS $toStatus, array $context = []): bool
    {
        $stateMachine = $this->createStateMachine($task, $context);
        return $stateMachine->transition($toStatus, $context);
    }

    /**
     * 获取任务可用的状态转换
     */
    public function getAvailableTransitions(Task $task, array $context = []): array
    {
        $stateMachine = $this->createStateMachine($task, $context);
        return $stateMachine->getAvailableTransitions();
    }

    /**
     * 验证状态转换并返回详细信息
     */
    public function validateTransition(Task $task, TASKSTATUS $toStatus, array $context = []): array
    {
        $stateMachine = $this->createStateMachine($task, $context);
        return $stateMachine->validateTransition($toStatus, $context);
    }

    /**
     * 批量转换任务状态
     */
    public function batchTransition(array $tasks, TASKSTATUS $toStatus, array $context = []): array
    {
        $results = [];
        
        foreach ($tasks as $task) {
            if (!$task instanceof Task) {
                $results[] = [
                    'task_id' => null,
                    'success' => false,
                    'error' => 'Invalid task instance',
                ];
                continue;
            }

            $success = $this->transition($task, $toStatus, $context);
            $results[] = [
                'task_id' => $task->id,
                'success' => $success,
                'error' => $success ? null : $this->getTransitionErrors($task, $toStatus, $context),
            ];
        }

        return $results;
    }

    /**
     * 获取转换错误信息
     */
    public function getTransitionErrors(Task $task, TASKSTATUS $toStatus, array $context = []): array
    {
        $stateMachine = $this->createStateMachine($task, $context);
        $stateMachine->canTransition($toStatus, $context);
        return $stateMachine->getErrors();
    }

    /**
     * 自动完成父任务（如果所有子任务都已完成）
     */
    public function autoCompleteParentTask(Task $subTask): bool
    {
        if (!$subTask->isSubTask() || !$subTask->parentTask) {
            return false;
        }

        $parentTask = $subTask->parentTask;
        
        // 检查是否启用自动完成
        if (!config('task.automation.auto_complete_parent_task', true)) {
            return false;
        }

        // 检查是否所有子任务都已完成
        if (!$parentTask->areAllSubTasksCompleted()) {
            return false;
        }

        // 检查父任务是否可以完成
        if (!$this->canTransition($parentTask, TASKSTATUS::COMPLETED)) {
            Log::warning('Cannot auto-complete parent task', [
                'parent_task_id' => $parentTask->id,
                'sub_task_id' => $subTask->id,
                'errors' => $this->getTransitionErrors($parentTask, TASKSTATUS::COMPLETED),
            ]);
            return false;
        }

        // 执行自动完成
        $success = $this->transition($parentTask, TASKSTATUS::COMPLETED, [
            'auto_completed' => true,
            'triggered_by_sub_task' => $subTask->id,
        ]);

        if ($success) {
            Log::info('Parent task auto-completed', [
                'parent_task_id' => $parentTask->id,
                'sub_task_id' => $subTask->id,
            ]);
        }

        return $success;
    }

    /**
     * 自动开始子任务（当父任务开始时）
     */
    public function autoStartSubTasks(Task $parentTask): array
    {
        if (!$parentTask->isMainTask()) {
            return [];
        }

        // 检查是否启用自动开始
        if (!config('task.automation.auto_start_sub_tasks', false)) {
            return [];
        }

        $results = [];
        $pendingSubTasks = $parentTask->subTasks()
            ->where('status', TASKSTATUS::PENDING->value)
            ->get();

        foreach ($pendingSubTasks as $subTask) {
            $success = $this->transition($subTask, TASKSTATUS::IN_PROGRESS, [
                'auto_started' => true,
                'triggered_by_parent_task' => $parentTask->id,
            ]);

            $results[] = [
                'sub_task_id' => $subTask->id,
                'success' => $success,
                'error' => $success ? null : $this->getTransitionErrors($subTask, TASKSTATUS::IN_PROGRESS),
            ];

            if ($success) {
                Log::info('Sub task auto-started', [
                    'sub_task_id' => $subTask->id,
                    'parent_task_id' => $parentTask->id,
                ]);
            }
        }

        return $results;
    }



    /**
     * 获取任务状态转换历史（如果有相关表的话）
     */
    public function getTransitionHistory(Task $task): array
    {
        // TODO: 如果需要状态转换历史记录，可以在这里实现
        // 需要创建相应的数据库表来存储转换历史
        return [];
    }

    /**
     * 检查任务工作流健康状态
     */
    public function checkWorkflowHealth(Task $task): array
    {
        $health = [
            'task_id' => $task->id,
            'current_status' => $task->status->value,
            'is_healthy' => true,
            'issues' => [],
            'recommendations' => [],
        ];

        // 检查子任务状态一致性
        if ($task->isMainTask()) {
            $this->checkSubTaskConsistency($task, $health);
        }

        // 检查父任务状态一致性
        if ($task->isSubTask()) {
            $this->checkParentTaskConsistency($task, $health);
        }

        return $health;
    }

    /**
     * 检查子任务状态一致性
     */
    private function checkSubTaskConsistency(Task $parentTask, array &$health): void
    {
        if ($parentTask->status === TASKSTATUS::COMPLETED) {
            $incompleteSubTasks = $parentTask->subTasks()
                ->whereNotIn('status', [TASKSTATUS::COMPLETED->value, TASKSTATUS::CANCELLED->value])
                ->count();

            if ($incompleteSubTasks > 0) {
                $health['is_healthy'] = false;
                $health['issues'][] = "主任务已完成但还有 {$incompleteSubTasks} 个未完成的子任务";
                $health['recommendations'][] = '检查子任务状态或重新评估主任务完成条件';
            }
        }
    }

    /**
     * 检查父任务状态一致性
     */
    private function checkParentTaskConsistency(Task $subTask, array &$health): void
    {
        if (!$subTask->parentTask) {
            return;
        }

        $parentStatus = $subTask->parentTask->status;
        $subStatus = $subTask->status;

        // 检查不一致的状态组合
        if ($parentStatus === TASKSTATUS::COMPLETED && $subStatus === TASKSTATUS::IN_PROGRESS) {
            $health['is_healthy'] = false;
            $health['issues'][] = '父任务已完成但子任务仍在进行中';
            $health['recommendations'][] = '完成或取消子任务';
        }

        if ($parentStatus === TASKSTATUS::CANCELLED && $subStatus->isActive()) {
            $health['is_healthy'] = false;
            $health['issues'][] = '父任务已取消但子任务仍处于活跃状态';
            $health['recommendations'][] = '取消子任务';
        }
    }
}
