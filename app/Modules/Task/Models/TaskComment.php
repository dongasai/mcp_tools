<?php

namespace App\Modules\Task\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Modules\User\Models\User;
use App\Modules\Agent\Models\Agent;
use App\Modules\Task\Enums\COMMENTTYPE;

class TaskComment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'task_id',
        'user_id',
        'agent_id',
        'parent_comment_id',
        'content',
        'comment_type',
        'metadata',
        'is_internal',
        'is_system',
        'attachments',
        'edited_at',
    ];

    protected $casts = [
        'comment_type' => COMMENTTYPE::class,
        'metadata' => 'array',
        'attachments' => 'array',
        'is_internal' => 'boolean',
        'is_system' => 'boolean',
        'edited_at' => 'datetime',
    ];

    protected $dates = [
        'edited_at',
        'deleted_at',
    ];

    /**
     * 关联任务
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * 关联用户
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 关联Agent
     */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    /**
     * 关联父评论
     */
    public function parentComment(): BelongsTo
    {
        return $this->belongsTo(TaskComment::class, 'parent_comment_id');
    }

    /**
     * 关联子评论（回复）
     */
    public function replies(): HasMany
    {
        return $this->hasMany(TaskComment::class, 'parent_comment_id');
    }

    /**
     * 获取评论作者名称
     */
    public function getAuthorNameAttribute(): string
    {
        if ($this->user_id) {
            return $this->user->name ?? 'Unknown User';
        }
        
        if ($this->agent_id) {
            return $this->agent->name ?? 'Unknown Agent';
        }
        
        return 'System';
    }

    /**
     * 获取评论作者类型
     */
    public function getAuthorTypeAttribute(): string
    {
        if ($this->user_id) {
            return 'user';
        }
        
        if ($this->agent_id) {
            return 'agent';
        }
        
        return 'system';
    }

    /**
     * 检查是否为回复评论
     */
    public function isReply(): bool
    {
        return !is_null($this->parent_comment_id);
    }

    /**
     * 检查是否为顶级评论
     */
    public function isTopLevel(): bool
    {
        return is_null($this->parent_comment_id);
    }

    /**
     * 检查评论是否已编辑
     */
    public function isEdited(): bool
    {
        return !is_null($this->edited_at);
    }

    /**
     * 检查用户是否可以编辑此评论
     */
    public function canEdit(User $user): bool
    {
        // 只有评论作者可以编辑
        if ($this->user_id !== $user->id) {
            return false;
        }

        // 系统评论不能编辑
        if ($this->is_system) {
            return false;
        }

        // 检查编辑时间限制（从配置获取，默认1小时）
        $editTimeLimit = config('task.comments.edit_time_limit', 3600);
        if ($editTimeLimit > 0) {
            return $this->created_at->addSeconds($editTimeLimit)->isFuture();
        }

        return true;
    }

    /**
     * 检查用户是否可以删除此评论
     */
    public function canDelete(User $user): bool
    {
        // 评论作者可以删除
        if ($this->user_id === $user->id) {
            return true;
        }

        // 任务创建者可以删除
        if ($this->task->user_id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * 获取评论的回复数量
     */
    public function getRepliesCountAttribute(): int
    {
        return $this->replies()->count();
    }

    /**
     * 作用域：获取顶级评论
     */
    public function scopeTopLevel($query)
    {
        return $query->whereNull('parent_comment_id');
    }

    /**
     * 作用域：获取回复评论
     */
    public function scopeReplies($query)
    {
        return $query->whereNotNull('parent_comment_id');
    }

    /**
     * 作用域：按评论类型过滤
     */
    public function scopeOfType($query, $type)
    {
        if ($type instanceof COMMENTTYPE) {
            return $query->where('comment_type', $type->value);
        }
        
        return $query->where('comment_type', $type);
    }

    /**
     * 作用域：获取公开评论
     */
    public function scopePublic($query)
    {
        return $query->where('is_internal', false);
    }

    /**
     * 作用域：获取内部评论
     */
    public function scopeInternal($query)
    {
        return $query->where('is_internal', true);
    }

    /**
     * 作用域：获取用户评论
     */
    public function scopeByUser($query)
    {
        return $query->whereNotNull('user_id');
    }

    /**
     * 作用域：获取Agent评论
     */
    public function scopeByAgent($query)
    {
        return $query->whereNotNull('agent_id');
    }

    /**
     * 作用域：获取系统评论
     */
    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }

    /**
     * 作用域：按时间排序
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * 作用域：按时间正序排序
     */
    public function scopeOldest($query)
    {
        return $query->orderBy('created_at', 'asc');
    }
}
