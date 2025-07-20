<?php

namespace App\Modules\Project\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Modules\User\Models\User;
use App\Modules\Agent\Models\Agent;

class Project extends Model
{
    protected $fillable = [
        'user_id',
        'agent_id',
        'name',
        'description',
        'repository_url',
        'branch',
        'status',
        'priority',
        'settings',
        'metadata',
    ];

    protected $casts = [
        'settings' => 'array',
        'metadata' => 'array',
    ];

    /**
     * 项目状态常量
     */
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_COMPLETED = 'completed';
    const STATUS_ARCHIVED = 'archived';
    const STATUS_SUSPENDED = 'suspended';

    /**
     * 项目优先级常量
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
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_INACTIVE => 'Inactive',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_ARCHIVED => 'Archived',
            self::STATUS_SUSPENDED => 'Suspended',
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
     * 关联任务
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(\App\Modules\Task\Models\Task::class);
    }

    /**
     * 关联项目成员
     */
    public function members(): HasMany
    {
        return $this->hasMany(\App\Modules\Project\Models\ProjectMember::class);
    }

    /**
     * 关联项目成员（包含用户信息）
     */
    public function membersWithUsers(): HasMany
    {
        return $this->hasMany(\App\Modules\Project\Models\ProjectMember::class)->with('user');
    }

    /**
     * 检查用户是否为项目成员
     */
    public function hasMember($user): bool
    {
        $userId = is_object($user) ? $user->id : $user;
        return $this->members()->where('user_id', $userId)->exists();
    }

    /**
     * 检查项目是否激活
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * 检查项目是否已完成
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * 激活项目
     */
    public function activate(): void
    {
        $this->update(['status' => self::STATUS_ACTIVE]);
    }

    /**
     * 完成项目
     */
    public function complete(): void
    {
        $this->update(['status' => self::STATUS_COMPLETED]);
    }

    /**
     * 归档项目
     */
    public function archive(): void
    {
        $this->update(['status' => self::STATUS_ARCHIVED]);
    }

    /**
     * 暂停项目
     */
    public function suspend(): void
    {
        $this->update(['status' => self::STATUS_SUSPENDED]);
    }

    /**
     * 获取项目统计信息
     */
    public function getStats(): array
    {
        return [
            'total_tasks' => $this->tasks()->count(),
            'completed_tasks' => $this->tasks()->where('status', 'completed')->count(),
            'active_tasks' => $this->tasks()->whereIn('status', ['pending', 'in_progress'])->count(),
            'blocked_tasks' => $this->tasks()->where('status', 'blocked')->count(),
            'completion_rate' => $this->getCompletionRate(),
        ];
    }

    /**
     * 获取完成率
     */
    public function getCompletionRate(): float
    {
        $totalTasks = $this->tasks()->count();
        if ($totalTasks === 0) {
            return 0.0;
        }

        $completedTasks = $this->tasks()->where('status', 'completed')->count();
        return round(($completedTasks / $totalTasks) * 100, 2);
    }

    /**
     * 获取设置值
     */
    public function getSetting(string $key, mixed $default = null): mixed
    {
        return data_get($this->settings, $key, $default);
    }

    /**
     * 设置配置值
     */
    public function setSetting(string $key, mixed $value): void
    {
        $settings = $this->settings ?? [];
        data_set($settings, $key, $value);
        $this->update(['settings' => $settings]);
    }

    /**
     * 查询作用域：按状态筛选
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * 查询作用域：激活的项目
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
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
     * 查询作用域：按优先级筛选
     */
    public function scopeByPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * 查询作用域：搜索项目
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%")
              ->orWhere('repository_url', 'like', "%{$search}%");
        });
    }
}
