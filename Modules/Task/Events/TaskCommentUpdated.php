<?php

namespace Modules\Task\Events;

use Modules\Task\Models\TaskComment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaskCommentUpdated
{
    use Dispatchable, SerializesModels;

    public TaskComment $comment;
    public array $originalData;

    /**
     * Create a new event instance.
     */
    public function __construct(TaskComment $comment, array $originalData = [])
    {
        $this->comment = $comment;
        $this->originalData = $originalData;
    }

    /**
     * 获取事件名称
     */
    public function getName(): string
    {
        return 'task.comment.updated';
    }

    /**
     * 获取事件数据
     */
    public function getData(): array
    {
        return [
            'comment_id' => $this->comment->id,
            'task_id' => $this->comment->task_id,
            'user_id' => $this->comment->user_id,
            'agent_id' => $this->comment->agent_id,
            'comment_type' => $this->comment->comment_type->value,
            'content' => $this->comment->content,
            'is_internal' => $this->comment->is_internal,
            'is_system' => $this->comment->is_system,
            'edited_at' => $this->comment->edited_at?->toISOString(),
            'updated_at' => $this->comment->updated_at->toISOString(),
            'original_data' => $this->originalData,
        ];
    }

    /**
     * 获取任务信息
     */
    public function getTask()
    {
        return $this->comment->task;
    }

    /**
     * 获取评论作者
     */
    public function getAuthor()
    {
        if ($this->comment->user_id) {
            return $this->comment->user;
        }
        
        if ($this->comment->agent_id) {
            return $this->comment->agent;
        }
        
        return null;
    }

    /**
     * 检查内容是否发生变化
     */
    public function hasContentChanged(): bool
    {
        return isset($this->originalData['content']) && 
               $this->originalData['content'] !== $this->comment->content;
    }

    /**
     * 检查类型是否发生变化
     */
    public function hasTypeChanged(): bool
    {
        return isset($this->originalData['comment_type']) && 
               $this->originalData['comment_type'] !== $this->comment->comment_type->value;
    }

    /**
     * 检查是否需要通知
     */
    public function shouldNotify(): bool
    {
        // 只有内容或类型发生重要变化时才通知
        return $this->hasContentChanged() || $this->hasTypeChanged();
    }
}
