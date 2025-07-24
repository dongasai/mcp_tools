<?php

namespace App\Modules\Task\Listeners;

use App\Modules\Task\Events\TaskAgentChanged;
use Illuminate\Support\Facades\Log;

class HandleTaskAgentChange
{
    /**
     * 处理任务Agent变更事件
     */
    public function handle(TaskAgentChanged $event): void
    {
        $task = $event->task;
        $oldAgentId = $event->previousAgentId;

        // 记录Agent变更日志
        Log::info('Task agent changed', [
            'task_id' => $task->id,
            'title' => $task->title,
            'old_agent_id' => $oldAgentId,
            'new_agent_id' => $task->agent_id,
            'user_id' => $task->user_id,
        ]);

        // 发送Agent变更通知
        if (config('task.notifications.agent_changed', true)) {
            $this->sendAgentChangeNotification($task, $oldAgentId);
        }

        // 处理Agent权限变更
        $this->handleAgentPermissions($task, $oldAgentId);

        // 更新Agent统计
        $this->updateAgentStatistics($task, $oldAgentId);

        // 处理任务转移
        $this->handleTaskTransfer($task, $oldAgentId);
    }

    /**
     * 发送Agent变更通知
     */
    private function sendAgentChangeNotification($task, $oldAgentId): void
    {
        $recipients = [];

        // 通知旧Agent
        if ($oldAgentId) {
            $recipients[] = [
                'type' => 'old_agent',
                'agent_id' => $oldAgentId,
                'message' => "Task '{$task->title}' has been reassigned",
            ];
        }

        // 通知新Agent
        if ($task->agent_id) {
            $recipients[] = [
                'type' => 'new_agent',
                'agent_id' => $task->agent_id,
                'message' => "Task '{$task->title}' has been assigned to you",
            ];
        }

        // 通知任务创建者
        if ($task->user_id) {
            $recipients[] = [
                'type' => 'task_owner',
                'user_id' => $task->user_id,
                'message' => "Agent assignment changed for task '{$task->title}'",
            ];
        }

        Log::debug('Agent change notifications prepared', [
            'task_id' => $task->id,
            'recipients_count' => count($recipients),
        ]);

        // TODO: 实际发送通知
        foreach ($recipients as $recipient) {
            $this->sendNotification($recipient);
        }
    }

    /**
     * 处理Agent权限变更
     */
    private function handleAgentPermissions($task, $oldAgentId): void
    {
        // 移除旧Agent的任务访问权限
        if ($oldAgentId) {
            Log::debug('Removing task permissions for old agent', [
                'task_id' => $task->id,
                'old_agent_id' => $oldAgentId,
            ]);
            
            // TODO: 实现权限移除逻辑
            $this->removeAgentTaskPermissions($oldAgentId, $task->id);
        }

        // 为新Agent添加任务访问权限
        if ($task->agent_id) {
            Log::debug('Adding task permissions for new agent', [
                'task_id' => $task->id,
                'new_agent_id' => $task->agent_id,
            ]);
            
            // TODO: 实现权限添加逻辑
            $this->addAgentTaskPermissions($task->agent_id, $task->id);
        }
    }

    /**
     * 更新Agent统计
     */
    private function updateAgentStatistics($task, $oldAgentId): void
    {
        // 更新旧Agent统计
        if ($oldAgentId) {
            // TODO: 减少旧Agent的任务计数
            Log::debug('Updating statistics for old agent', [
                'agent_id' => $oldAgentId,
                'action' => 'remove_task',
            ]);
        }

        // 更新新Agent统计
        if ($task->agent_id) {
            // TODO: 增加新Agent的任务计数
            Log::debug('Updating statistics for new agent', [
                'agent_id' => $task->agent_id,
                'action' => 'add_task',
            ]);
        }
    }

    /**
     * 处理任务转移
     */
    private function handleTaskTransfer($task, $oldAgentId): void
    {
        // 如果任务正在进行中，需要特殊处理
        if ($task->status === 'in_progress') {
            Log::warning('Active task transferred between agents', [
                'task_id' => $task->id,
                'old_agent_id' => $oldAgentId,
                'new_agent_id' => $task->agent_id,
            ]);

            // TODO: 实现任务转移逻辑
            // 1. 保存当前进度
            // 2. 创建转移记录
            // 3. 通知相关人员
        }

        // 处理子任务的Agent继承
        if (config('task.automation.auto_assign_to_agent', false)) {
            $this->handleSubTaskAgentInheritance($task);
        }
    }

    /**
     * 处理子任务Agent继承
     */
    private function handleSubTaskAgentInheritance($task): void
    {
        $subTasks = $task->subTasks()->whereNull('agent_id')->get();

        foreach ($subTasks as $subTask) {
            $subTask->update(['agent_id' => $task->agent_id]);
            
            Log::debug('Sub task inherited agent assignment', [
                'sub_task_id' => $subTask->id,
                'parent_task_id' => $task->id,
                'agent_id' => $task->agent_id,
            ]);
        }
    }

    /**
     * 发送通知
     */
    private function sendNotification(array $recipient): void
    {
        // TODO: 实现实际的通知发送逻辑
        Log::debug('Notification sent', $recipient);
    }

    /**
     * 移除Agent任务权限
     */
    private function removeAgentTaskPermissions($agentId, $taskId): void
    {
        // TODO: 实现权限移除逻辑
    }

    /**
     * 添加Agent任务权限
     */
    private function addAgentTaskPermissions($agentId, $taskId): void
    {
        // TODO: 实现权限添加逻辑
    }
}
