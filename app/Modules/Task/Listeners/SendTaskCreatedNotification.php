<?php

namespace App\Modules\Task\Listeners;

use App\Modules\Task\Events\TaskCreated;
use Illuminate\Support\Facades\Log;

class SendTaskCreatedNotification
{
    /**
     * 处理任务创建事件
     */
    public function handle(TaskCreated $event): void
    {
        $task = $event->task;

        // 记录任务创建日志
        Log::info('Task created', [
            'task_id' => $task->id,
            'title' => $task->title,
            'type' => $task->type,
            'user_id' => $task->user_id,
            'project_id' => $task->project_id,
        ]);

        // 检查是否启用通知
        if (!config('task.notifications.task_created', true)) {
            return;
        }

        // TODO: 实现通知逻辑
        // 1. 通知项目成员
        // 2. 通知分配的用户
        // 3. 发送邮件/短信通知
        // 4. 推送到消息队列

        Log::debug('Task creation notification sent', [
            'task_id' => $task->id,
            'recipients' => $this->getNotificationRecipients($task),
        ]);
    }

    /**
     * 获取通知接收者
     */
    private function getNotificationRecipients($task): array
    {
        $recipients = [];

        // 添加任务创建者
        if ($task->user) {
            $recipients[] = [
                'type' => 'creator',
                'user_id' => $task->user_id,
                'email' => $task->user->email,
            ];
        }

        // 添加分配的用户
        if ($task->assigned_to && $task->assignedUser) {
            $recipients[] = [
                'type' => 'assignee',
                'user_id' => $task->assigned_to,
                'email' => $task->assignedUser->email,
            ];
        }

        // 添加项目成员
        if ($task->project) {
            // TODO: 获取项目成员列表
        }

        return $recipients;
    }
}
