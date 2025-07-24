<?php

namespace App\Modules\Task\Listeners;

use App\Modules\Task\Events\TaskCommentCreated;
use App\Modules\Task\Events\TaskCommentUpdated;
use App\Modules\Task\Events\TaskCommentDeleted;
use Illuminate\Support\Facades\Log;

class HandleTaskCommentEvents
{
    /**
     * 处理评论创建事件
     */
    public function handleCommentCreated(TaskCommentCreated $event): void
    {
        $comment = $event->comment;
        $task = $comment->task;

        Log::info('Task comment created', [
            'comment_id' => $comment->id,
            'task_id' => $task->id,
            'user_id' => $comment->user_id,
            'type' => $comment->type,
        ]);

        // 发送评论通知
        if (config('task.notifications.comment_created', true)) {
            $this->sendCommentNotification($comment, 'created');
        }

        // 更新任务活动时间
        $this->updateTaskActivity($task);

        // 处理特殊类型的评论
        $this->handleSpecialCommentTypes($comment);
    }

    /**
     * 处理评论更新事件
     */
    public function handleCommentUpdated(TaskCommentUpdated $event): void
    {
        $comment = $event->comment;
        $task = $comment->task;

        Log::info('Task comment updated', [
            'comment_id' => $comment->id,
            'task_id' => $task->id,
            'user_id' => $comment->user_id,
        ]);

        // 发送更新通知（可选）
        if (config('task.notifications.comment_updated', false)) {
            $this->sendCommentNotification($comment, 'updated');
        }

        // 更新任务活动时间
        $this->updateTaskActivity($task);
    }

    /**
     * 处理评论删除事件
     */
    public function handleCommentDeleted(TaskCommentDeleted $event): void
    {
        $comment = $event->comment;
        $task = $comment->task;

        Log::info('Task comment deleted', [
            'comment_id' => $comment->id,
            'task_id' => $task->id,
            'user_id' => $comment->user_id,
        ]);

        // 发送删除通知（可选）
        if (config('task.notifications.comment_deleted', false)) {
            $this->sendCommentNotification($comment, 'deleted');
        }
    }

    /**
     * 发送评论通知
     */
    private function sendCommentNotification($comment, string $action): void
    {
        $task = $comment->task;
        $recipients = $this->getCommentNotificationRecipients($comment);

        Log::debug('Sending comment notification', [
            'comment_id' => $comment->id,
            'task_id' => $task->id,
            'action' => $action,
            'recipients_count' => count($recipients),
        ]);

        // TODO: 实现通知发送逻辑
        foreach ($recipients as $recipient) {
            $this->sendNotification($recipient, $comment, $action);
        }
    }

    /**
     * 获取评论通知接收者
     */
    private function getCommentNotificationRecipients($comment): array
    {
        $task = $comment->task;
        $recipients = [];

        // 通知任务创建者（如果不是评论者本人）
        if ($task->user_id && $task->user_id !== $comment->user_id) {
            $recipients[] = [
                'type' => 'task_owner',
                'user_id' => $task->user_id,
                'email' => $task->user->email ?? null,
            ];
        }

        // 通知任务分配者（如果不是评论者本人）
        if ($task->assigned_to && $task->assigned_to !== $comment->user_id) {
            $recipients[] = [
                'type' => 'assignee',
                'user_id' => $task->assigned_to,
                'email' => $task->assignedUser->email ?? null,
            ];
        }

        // 通知任务相关的Agent
        if ($task->agent_id) {
            $recipients[] = [
                'type' => 'agent',
                'agent_id' => $task->agent_id,
            ];
        }

        // 通知其他评论者（排除当前评论者）
        $otherCommenters = $task->comments()
            ->where('user_id', '!=', $comment->user_id)
            ->distinct('user_id')
            ->with('user')
            ->get();

        foreach ($otherCommenters as $otherComment) {
            if ($otherComment->user) {
                $recipients[] = [
                    'type' => 'commenter',
                    'user_id' => $otherComment->user_id,
                    'email' => $otherComment->user->email,
                ];
            }
        }

        // 去重
        $uniqueRecipients = [];
        foreach ($recipients as $recipient) {
            $key = $recipient['user_id'] ?? $recipient['agent_id'];
            $uniqueRecipients[$key] = $recipient;
        }

        return array_values($uniqueRecipients);
    }

    /**
     * 更新任务活动时间
     */
    private function updateTaskActivity($task): void
    {
        // 更新任务的最后活动时间
        $task->touch();

        Log::debug('Task activity updated', [
            'task_id' => $task->id,
            'updated_at' => $task->updated_at,
        ]);
    }

    /**
     * 处理特殊类型的评论
     */
    private function handleSpecialCommentTypes($comment): void
    {
        switch ($comment->type) {
            case 'status_change':
                $this->handleStatusChangeComment($comment);
                break;
            case 'progress_update':
                $this->handleProgressUpdateComment($comment);
                break;
            case 'agent_assignment':
                $this->handleAgentAssignmentComment($comment);
                break;
            case 'system':
                $this->handleSystemComment($comment);
                break;
            default:
                // 普通评论，无需特殊处理
                break;
        }
    }

    /**
     * 处理状态变更评论
     */
    private function handleStatusChangeComment($comment): void
    {
        Log::debug('Processing status change comment', [
            'comment_id' => $comment->id,
            'task_id' => $comment->task_id,
        ]);

        // TODO: 解析状态变更信息，可能需要更新任务状态
    }

    /**
     * 处理进度更新评论
     */
    private function handleProgressUpdateComment($comment): void
    {
        Log::debug('Processing progress update comment', [
            'comment_id' => $comment->id,
            'task_id' => $comment->task_id,
        ]);

        // TODO: 解析进度信息，可能需要更新任务进度
    }

    /**
     * 处理Agent分配评论
     */
    private function handleAgentAssignmentComment($comment): void
    {
        Log::debug('Processing agent assignment comment', [
            'comment_id' => $comment->id,
            'task_id' => $comment->task_id,
        ]);

        // TODO: 解析Agent分配信息
    }

    /**
     * 处理系统评论
     */
    private function handleSystemComment($comment): void
    {
        Log::debug('Processing system comment', [
            'comment_id' => $comment->id,
            'task_id' => $comment->task_id,
        ]);

        // 系统评论通常不需要发送通知
    }

    /**
     * 发送通知
     */
    private function sendNotification(array $recipient, $comment, string $action): void
    {
        // TODO: 实现实际的通知发送逻辑
        Log::debug('Comment notification sent', [
            'recipient' => $recipient,
            'comment_id' => $comment->id,
            'action' => $action,
        ]);
    }
}
