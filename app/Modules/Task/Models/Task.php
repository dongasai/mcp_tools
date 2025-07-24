<?php

namespace App\Modules\Task\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Modules\Project\Models\Project;
use App\Modules\User\Models\User;
use App\Modules\Agent\Models\Agent;
use App\Modules\Task\Enums\TASKSTATUS;
use App\Modules\Task\Enums\TASKTYPE;
use App\Modules\Task\Enums\TASKPRIORITY;

class Task extends Model
{
    protected $fillable = [
        'user_id',
        'agent_id',
        'project_id',
        'parent_task_id',
        'title',
        'description',
        'type',
        'status',
        'priority',
        'assigned_to',
        'due_date',
        'estimated_hours',
        'actual_hours',
        'progress',
        'tags',
        'metadata',
        'result',
    ];

    protected $casts = [
        'type' => TASKTYPE::class,
        'status' => TASKSTATUS::class,
        'priority' => TASKPRIORITY::class,
        'due_date' => 'datetime',
        'estimated_hours' => 'decimal:2',
        'actual_hours' => 'decimal:2',
        'progress' => 'integer',
        'tags' => 'array',
        'metadata' => 'array',
        'result' => 'array',
    ];

    /**
     * 获取任务创建者
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 获取拥有此任务的项目
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * 获取分配给此任务的用户
     */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * 获取分配给此任务的Agent
     */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    /**
     * 获取父任务
     */
    public function parentTask(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'parent_task_id');
    }

    /**
     * 获取子任务
     */
    public function subTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'parent_task_id');
    }

    /**
     * 获取任务评论
     */
    public function comments(): HasMany
    {
        return $this->hasMany(\App\Modules\Task\Models\TaskComment::class);
    }

    /**
     * 查询范围：仅包含待处理的任务
     */
    public function scopePending($query)
    {
        return $query->where('status', TASKSTATUS::PENDING);
    }

    /**
     * 查询范围：仅包含进行中的任务
     */
    public function scopeInProgress($query)
    {
        return $query->where('status', TASKSTATUS::IN_PROGRESS);
    }

    /**
     * 查询范围：仅包含已完成的任务
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', TASKSTATUS::COMPLETED);
    }

    /**
     * 查询范围：仅包含已阻塞的任务
     */
    public function scopeBlocked($query)
    {
        return $query->where('status', TASKSTATUS::BLOCKED);
    }

    /**
     * 查询范围：仅包含主任务
     */
    public function scopeMainTasks($query)
    {
        return $query->where('type', TASKTYPE::MAIN);
    }

    /**
     * 查询范围：仅包含子任务
     */
    public function scopeSubTasks($query)
    {
        return $query->where('type', TASKTYPE::SUB);
    }

    /**
     * 查询范围：按状态筛选
     */
    public function scopeByStatus($query, TASKSTATUS|string $status)
    {
        if ($status instanceof TASKSTATUS) {
            return $query->where('status', $status);
        }
        return $query->where('status', $status);
    }

    /**
     * 查询范围：按类型筛选
     */
    public function scopeByType($query, TASKTYPE|string $type)
    {
        if ($type instanceof TASKTYPE) {
            return $query->where('type', $type);
        }
        return $query->where('type', $type);
    }

    /**
     * 查询范围：按优先级筛选
     */
    public function scopeByPriority($query, TASKPRIORITY|string $priority)
    {
        if ($priority instanceof TASKPRIORITY) {
            return $query->where('priority', $priority);
        }
        return $query->where('priority', $priority);
    }

    /**
     * 查询范围：按Agent筛选
     */
    public function scopeByAgent($query, $agentId)
    {
        return $query->where('agent_id', $agentId);
    }

    /**
     * 查询范围：按用户筛选
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * 查询范围：按项目筛选
     */
    public function scopeByProject($query, $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    /**
     * 检查任务是否已过期
     */
    public function isOverdue(): bool
    {
        return $this->due_date && $this->due_date->isPast() &&
               !in_array($this->status, [TASKSTATUS::COMPLETED, TASKSTATUS::CANCELLED]);
    }

    /**
     * 检查是否为主任务
     */
    public function isMainTask(): bool
    {
        return $this->type === TASKTYPE::MAIN;
    }

    /**
     * 检查是否为子任务
     */
    public function isSubTask(): bool
    {
        return $this->type === TASKTYPE::SUB;
    }

    /**
     * 检查任务是否已完成
     */
    public function isCompleted(): bool
    {
        return $this->status === TASKSTATUS::COMPLETED;
    }

    /**
     * 检查任务是否活跃（待处理或进行中）
     */
    public function isActive(): bool
    {
        return in_array($this->status, [TASKSTATUS::PENDING, TASKSTATUS::IN_PROGRESS]);
    }

    /**
     * 检查任务是否被阻塞
     */
    public function isBlocked(): bool
    {
        return $this->status === TASKSTATUS::BLOCKED;
    }

    /**
     * 完成任务
     */
    public function complete(): void
    {
        $this->update([
            'status' => TASKSTATUS::COMPLETED,
            'progress' => 100,
        ]);
    }

    /**
     * 开始任务
     */
    public function start(): void
    {
        $this->update([
            'status' => TASKSTATUS::IN_PROGRESS,
        ]);
    }

    /**
     * 阻塞任务
     */
    public function block(): void
    {
        $this->update([
            'status' => TASKSTATUS::BLOCKED,
        ]);
    }

    /**
     * 取消任务
     */
    public function cancel(): void
    {
        $this->update([
            'status' => TASKSTATUS::CANCELLED,
        ]);
    }

    /**
     * 获取任务进度百分比
     */
    public function getProgressPercentage(): int
    {
        return max(0, min(100, $this->progress));
    }

    /**
     * 更新任务进度
     */
    public function updateProgress(int $progress): void
    {
        $this->update([
            'progress' => max(0, min(100, $progress)),
        ]);
    }

    /**
     * 获取子任务完成度
     */
    public function getSubTasksCompletionRate(): float
    {
        $subTasks = $this->subTasks;
        if ($subTasks->isEmpty()) {
            return 0.0;
        }

        $completedCount = $subTasks->where('status', TASKSTATUS::COMPLETED)->count();
        return $completedCount / $subTasks->count();
    }

    /**
     * 检查是否所有子任务都已完成
     */
    public function areAllSubTasksCompleted(): bool
    {
        return $this->subTasks()->where('status', '!=', TASKSTATUS::COMPLETED)->count() === 0;
    }
}
