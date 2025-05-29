<?php

namespace App\Modules\Task\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Modules\User\Models\User;
use App\Modules\Agent\Models\Agent;
use App\Modules\Project\Models\Project;

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
        'tags' => 'array',
        'metadata' => 'array',
        'result' => 'array',
        'due_date' => 'datetime',
        'progress' => 'integer',
        'estimated_hours' => 'decimal:2',
        'actual_hours' => 'decimal:2',
    ];

    /**
     * 任务状态常量
     */
    const STATUS_PENDING = 'pending';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_BLOCKED = 'blocked';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_ON_HOLD = 'on_hold';

    /**
     * 任务类型常量
     */
    const TYPE_MAIN = 'main';
    const TYPE_SUB = 'sub';
    const TYPE_MILESTONE = 'milestone';
    const TYPE_BUG = 'bug';
    const TYPE_FEATURE = 'feature';
    const TYPE_IMPROVEMENT = 'improvement';

    /**
     * 任务优先级常量
     */
    const PRIORITY_LOW = 'low';
    const PRIORITY_MEDIUM = 'medium';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_URGENT = 'urgent';

    /**
     * 获取所有可用状态
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_BLOCKED => 'Blocked',
            self::STATUS_CANCELLED => 'Cancelled',
            self::STATUS_ON_HOLD => 'On Hold',
        ];
    }

    /**
     * 获取所有任务类型
     */
    public static function getTypes(): array
    {
        return [
            self::TYPE_MAIN => 'Main Task',
            self::TYPE_SUB => 'Sub Task',
            self::TYPE_MILESTONE => 'Milestone',
            self::TYPE_BUG => 'Bug Fix',
            self::TYPE_FEATURE => 'Feature',
            self::TYPE_IMPROVEMENT => 'Improvement',
        ];
    }

    /**
     * 获取所有优先级
     */
    public static function getPriorities(): array
    {
        return [
            self::PRIORITY_LOW => 'Low',
            self::PRIORITY_MEDIUM => 'Medium',
            self::PRIORITY_HIGH => 'High',
            self::PRIORITY_URGENT => 'Urgent',
        ];
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
     * 关联项目
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * 关联父任务
     */
    public function parentTask(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'parent_task_id');
    }

    /**
     * 关联子任务
     */
    public function subTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'parent_task_id');
    }

    /**
     * 检查任务是否为主任务
     */
    public function isMainTask(): bool
    {
        return $this->type === self::TYPE_MAIN || $this->parent_task_id === null;
    }

    /**
     * 检查任务是否为子任务
     */
    public function isSubTask(): bool
    {
        return $this->parent_task_id !== null;
    }

    /**
     * 检查任务是否已完成
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * 检查任务是否进行中
     */
    public function isInProgress(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    /**
     * 检查任务是否被阻塞
     */
    public function isBlocked(): bool
    {
        return $this->status === self::STATUS_BLOCKED;
    }

    /**
     * 开始任务
     */
    public function start(): void
    {
        $this->update(['status' => self::STATUS_IN_PROGRESS]);
    }

    /**
     * 完成任务
     */
    public function complete(): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'progress' => 100,
        ]);
    }

    /**
     * 阻塞任务
     */
    public function block(): void
    {
        $this->update(['status' => self::STATUS_BLOCKED]);
    }

    /**
     * 取消任务
     */
    public function cancel(): void
    {
        $this->update(['status' => self::STATUS_CANCELLED]);
    }

    /**
     * 更新进度
     */
    public function updateProgress(int $progress): void
    {
        $progress = max(0, min(100, $progress));
        $this->update(['progress' => $progress]);

        // 如果进度达到100%，自动完成任务
        if ($progress === 100 && !$this->isCompleted()) {
            $this->complete();
        }
    }

    /**
     * 获取完成率
     */
    public function getCompletionRate(): float
    {
        if ($this->isMainTask()) {
            $subTasks = $this->subTasks;
            if ($subTasks->isEmpty()) {
                return (float) $this->progress;
            }

            $totalProgress = $subTasks->sum('progress');
            $taskCount = $subTasks->count();
            return $taskCount > 0 ? round($totalProgress / $taskCount, 2) : 0.0;
        }

        return (float) $this->progress;
    }

    /**
     * 查询作用域：按状态筛选
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * 查询作用域：按类型筛选
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * 查询作用域：主任务
     */
    public function scopeMainTasks($query)
    {
        return $query->whereNull('parent_task_id');
    }

    /**
     * 查询作用域：子任务
     */
    public function scopeSubTasks($query)
    {
        return $query->whereNotNull('parent_task_id');
    }

    /**
     * 查询作用域：按用户筛选
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * 查询作用域：按Agent筛选
     */
    public function scopeByAgent($query, int $agentId)
    {
        return $query->where('agent_id', $agentId);
    }

    /**
     * 查询作用域：按项目筛选
     */
    public function scopeByProject($query, int $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    /**
     * 查询作用域：按优先级筛选
     */
    public function scopeByPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * 查询作用域：搜索任务
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%");
        });
    }

    /**
     * 查询作用域：即将到期的任务
     */
    public function scopeDueSoon($query, int $days = 7)
    {
        return $query->where('due_date', '<=', now()->addDays($days))
                    ->where('status', '!=', self::STATUS_COMPLETED);
    }

    /**
     * 查询作用域：已逾期的任务
     */
    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())
                    ->where('status', '!=', self::STATUS_COMPLETED);
    }
}
