<?php

namespace Modules\Task\Listeners;

use Modules\Task\Events\TaskStatusChanged;
use Modules\Task\Enums\TASKSTATUS;
use Illuminate\Support\Facades\Log;

class HandleTaskStatusChange
{
    /**
     * 处理任务状态变更事件
     */
    public function handle(TaskStatusChanged $event): void
    {
        $task = $event->task;
        $oldStatus = $event->previousStatus;

        // 记录状态变更日志
        Log::info('Task status changed', [
            'task_id' => $task->id,
            'title' => $task->title,
            'old_status' => $oldStatus,
            'new_status' => $task->status,
            'user_id' => $task->user_id,
        ]);

        // 根据新状态执行相应的处理逻辑
        match ($task->status) {
            TASKSTATUS::IN_PROGRESS => $this->handleTaskStarted($task),
            TASKSTATUS::COMPLETED => $this->handleTaskCompleted($task),
            TASKSTATUS::BLOCKED => $this->handleTaskBlocked($task),
            TASKSTATUS::CANCELLED => $this->handleTaskCancelled($task),
            default => null,
        };

        // 发送状态变更通知
        if (config('task.notifications.status_changed', true)) {
            $this->sendStatusChangeNotification($task, $oldStatus);
        }

        // 更新相关统计数据
        $this->updateTaskStatistics($task, $oldStatus);
    }

    /**
     * 处理任务开始
     */
    private function handleTaskStarted($task): void
    {
        Log::debug('Task started processing', ['task_id' => $task->id]);

        // 自动开始子任务（如果配置启用）
        if (config('task.automation.auto_start_sub_tasks', false)) {
            $this->autoStartSubTasks($task);
        }
    }

    /**
     * 处理任务完成
     */
    private function handleTaskCompleted($task): void
    {
        Log::debug('Task completed processing', ['task_id' => $task->id]);

        // 检查父任务是否应该完成
        if ($task->parent_task_id && config('task.automation.auto_complete_parent_task', true)) {
            $this->checkParentTaskCompletion($task);
        }

        // 更新任务进度为100%
        if ($task->progress < 100) {
            $task->update(['progress' => 100]);
        }
    }

    /**
     * 处理任务阻塞
     */
    private function handleTaskBlocked($task): void
    {
        Log::warning('Task blocked', [
            'task_id' => $task->id,
            'title' => $task->title,
        ]);

        // TODO: 实现阻塞处理逻辑
        // 1. 通知相关人员
        // 2. 暂停相关子任务
        // 3. 记录阻塞原因
    }

    /**
     * 处理任务取消
     */
    private function handleTaskCancelled($task): void
    {
        Log::info('Task cancelled', [
            'task_id' => $task->id,
            'title' => $task->title,
        ]);

        // TODO: 实现取消处理逻辑
        // 1. 取消相关子任务
        // 2. 释放资源
        // 3. 通知相关人员
    }

    /**
     * 发送状态变更通知
     */
    private function sendStatusChangeNotification($task, $oldStatus): void
    {
        Log::debug('Sending status change notification', [
            'task_id' => $task->id,
            'old_status' => $oldStatus,
            'new_status' => $task->status,
        ]);

        // TODO: 实现通知逻辑
    }

    /**
     * 更新任务统计数据
     */
    private function updateTaskStatistics($task, $oldStatus): void
    {
        // TODO: 更新项目统计、用户统计等
        Log::debug('Task statistics updated', [
            'task_id' => $task->id,
            'project_id' => $task->project_id,
        ]);
    }

    /**
     * 自动开始子任务
     */
    private function autoStartSubTasks($task): void
    {
        $pendingSubTasks = $task->subTasks()->where('status', TASKSTATUS::PENDING)->get();
        
        foreach ($pendingSubTasks as $subTask) {
            $subTask->update(['status' => TASKSTATUS::IN_PROGRESS]);
            Log::debug('Auto-started sub task', ['sub_task_id' => $subTask->id]);
        }
    }

    /**
     * 检查父任务完成条件
     */
    private function checkParentTaskCompletion($task): void
    {
        if (!$task->parentTask) {
            return;
        }

        $parentTask = $task->parentTask;
        
        // 检查是否所有子任务都已完成
        if ($parentTask->areAllSubTasksCompleted()) {
            $parentTask->update(['status' => TASKSTATUS::COMPLETED]);
            Log::info('Parent task auto-completed', ['parent_task_id' => $parentTask->id]);
        }
    }
}
