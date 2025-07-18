<?php

namespace App\Modules\Agent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Modules\User\Models\User;

class Agent extends Model
{

    protected $fillable = [
        'user_id',
        'name',
        'identifier',
        'status',
        'description',
        'capabilities',
        'configuration',
        'last_active_at',
    ];

    protected $casts = [
        'permissions' => 'array',
        'allowed_projects' => 'array',
        'allowed_actions' => 'array',
        'last_active_at' => 'datetime',
        'token_expires_at' => 'datetime',
    ];

    protected $dates = [
        'last_active_at',
        'token_expires_at',
    ];

    /**
     * Agent状态常量
     */
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_SUSPENDED = 'suspended';
    const STATUS_PENDING = 'pending';

    /**
     * 获取所有可用状态
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_INACTIVE => 'Inactive',
            self::STATUS_SUSPENDED => 'Suspended',
            self::STATUS_PENDING => 'Pending',
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
     * 关联任务
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(\App\Modules\Task\Models\Task::class);
    }

    /**
     * 关联项目
     */
    public function projects(): HasMany
    {
        return $this->hasMany(\App\Modules\Project\Models\Project::class);
    }

    /**
     * 检查Agent是否激活
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * 检查Agent是否可用
     */
    public function isAvailable(): bool
    {
        return in_array($this->status, [self::STATUS_ACTIVE, self::STATUS_PENDING]);
    }

    /**
     * 激活Agent
     */
    public function activate(): void
    {
        $this->update(['status' => self::STATUS_ACTIVE]);
    }

    /**
     * 停用Agent
     */
    public function deactivate(): void
    {
        $this->update(['status' => self::STATUS_INACTIVE]);
    }

    /**
     * 暂停Agent
     */
    public function suspend(): void
    {
        $this->update(['status' => self::STATUS_SUSPENDED]);
    }

    /**
     * 更新最后活跃时间
     */
    public function updateLastActive(): void
    {
        $this->update(['last_active_at' => now()]);
    }

    /**
     * 获取Agent统计信息
     */
    public function getStats(): array
    {
        return [
            'total_tasks' => $this->tasks()->count(),
            'completed_tasks' => $this->tasks()->where('status', 'completed')->count(),
            'active_tasks' => $this->tasks()->whereIn('status', ['pending', 'in_progress'])->count(),
            'total_projects' => $this->projects()->count(),
            'last_active' => $this->last_active_at?->diffForHumans(),
        ];
    }

    /**
     * 检查Agent是否有指定动作权限
     */
    public function hasAction(string $action): bool
    {
        return in_array($action, $this->allowed_actions ?? []);
    }

    /**
     * 添加动作权限
     */
    public function addAction(string $action): void
    {
        $actions = $this->allowed_actions ?? [];
        if (!in_array($action, $actions)) {
            $actions[] = $action;
            $this->update(['allowed_actions' => $actions]);
        }
    }

    /**
     * 移除动作权限
     */
    public function removeAction(string $action): void
    {
        $actions = $this->allowed_actions ?? [];
        $actions = array_filter($actions, fn($act) => $act !== $action);
        $this->update(['allowed_actions' => array_values($actions)]);
    }

    /**
     * 检查Agent是否有项目权限
     */
    public function hasProjectAccess(int $projectId): bool
    {
        return in_array($projectId, $this->allowed_projects ?? []);
    }

    /**
     * 添加项目权限
     */
    public function addProjectAccess(int $projectId): void
    {
        $projects = $this->allowed_projects ?? [];
        if (!in_array($projectId, $projects)) {
            $projects[] = $projectId;
            $this->update(['allowed_projects' => $projects]);
        }
    }

    /**
     * 移除项目权限
     */
    public function removeProjectAccess(int $projectId): void
    {
        $projects = $this->allowed_projects ?? [];
        $projects = array_filter($projects, fn($pid) => $pid !== $projectId);
        $this->update(['allowed_projects' => array_values($projects)]);
    }

    /**
     * 查询作用域：按状态筛选
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * 查询作用域：激活的Agent
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * 查询作用域：可用的Agent
     */
    public function scopeAvailable($query)
    {
        return $query->whereIn('status', [self::STATUS_ACTIVE, self::STATUS_PENDING]);
    }

    /**
     * 查询作用域：按用户筛选
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * 查询作用域：按动作权限筛选
     */
    public function scopeWithAction($query, string $action)
    {
        return $query->whereJsonContains('allowed_actions', $action);
    }

    /**
     * 查询作用域：按项目权限筛选
     */
    public function scopeWithProjectAccess($query, int $projectId)
    {
        return $query->whereJsonContains('allowed_projects', $projectId);
    }
}
