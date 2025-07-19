<?php

namespace App\Modules\Task\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Modules\User\Models\User;
use App\Modules\Agent\Models\Agent;
use App\Modules\Project\Models\Project;
use App\Modules\Task\Enums\TaskStatus;
use App\Modules\Task\Enums\TaskType;
use App\Modules\Task\Enums\TaskPriority;

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
        'status' => TaskStatus::class,
        'type' => TaskType::class,
        'priority' => TaskPriority::class,
        'tags' => 'array',
        'metadata' => 'array',
        'result' => 'array',
        'due_date' => 'datetime',
        'progress' => 'integer',
        'estimated_hours' => 'decimal:2',
        'actual_hours' => 'decimal:2',
    ];

    /**
     * 获取所有可用状态
     */
    public static function getStatuses(): array
    {
        return TaskStatus::selectOptions();
    }

    /**
     * 获取所有任务类型
     */
    public static function getTypes(): array
    {
        return TaskType::selectOptions();
    }

    /**
     * 获取所有优先级
     */
    public static function getPriorities(): array
    {
        return TaskPriority::selectOptions();
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
        return $this->type === TaskType::MAIN || $this->parent_task_id === null;
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
        return $this->status === TaskStatus::COMPLETED;
    }

    /**
     * 检查任务是否进行中
     */
    public function isInProgress(): bool
    {
        return $this->status === TaskStatus::IN_PROGRESS;
    }

    /**
     * 检查任务是否被阻塞
     */
    public function isBlocked(): bool
    {
        return $this->status === TaskStatus::BLOCKED;
    }

    /**
     * 开始任务
     */
    public function start(): void
    {
        $this->update(['status' => TaskStatus::IN_PROGRESS]);
    }

    /**
     * 完成任务
     */
    public function complete(): void
    {
        $this->update([
            'status' => TaskStatus::COMPLETED,
            'progress' => 100,
        ]);
    }

    /**
     * 阻塞任务
     */
    public function block(): void
    {
        $this->update(['status' => TaskStatus::BLOCKED]);
    }

    /**
     * 取消任务
     */
    public function cancel(): void
    {
        $this->update(['status' => TaskStatus::CANCELLED]);
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
    public function scopeByStatus($query, TaskStatus|string $status)
    {
        $statusValue = $status instanceof TaskStatus ? $status->value : $status;
        return $query->where('status', $statusValue);
    }

    /**
     * 查询作用域：按类型筛选
     */
    public function scopeByType($query, TaskType|string $type)
    {
        $typeValue = $type instanceof TaskType ? $type->value : $type;
        return $query->where('type', $typeValue);
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
    public function scopeByPriority($query, TaskPriority|string $priority)
    {
        $priorityValue = $priority instanceof TaskPriority ? $priority->value : $priority;
        return $query->where('priority', $priorityValue);
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
                    ->where('status', '!=', TaskStatus::COMPLETED);
    }

    /**
     * 查询作用域：已逾期的任务
     */
    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())
                    ->where('status', '!=', TaskStatus::COMPLETED);
    }
}
