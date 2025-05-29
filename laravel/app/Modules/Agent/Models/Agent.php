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
        'description',
        'agent_id',
        'capabilities',
        'configuration',
        'status',
        'last_active_at',
        'metadata',
    ];

    protected $casts = [
        'capabilities' => 'array',
        'configuration' => 'array',
        'metadata' => 'array',
        'last_active_at' => 'datetime',
    ];

    protected $dates = [
        'last_active_at',
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
     * 检查Agent是否有指定能力
     */
    public function hasCapability(string $capability): bool
    {
        return in_array($capability, $this->capabilities ?? []);
    }

    /**
     * 添加能力
     */
    public function addCapability(string $capability): void
    {
        $capabilities = $this->capabilities ?? [];
        if (!in_array($capability, $capabilities)) {
            $capabilities[] = $capability;
            $this->update(['capabilities' => $capabilities]);
        }
    }

    /**
     * 移除能力
     */
    public function removeCapability(string $capability): void
    {
        $capabilities = $this->capabilities ?? [];
        $capabilities = array_filter($capabilities, fn($cap) => $cap !== $capability);
        $this->update(['capabilities' => array_values($capabilities)]);
    }

    /**
     * 获取配置值
     */
    public function getConfig(string $key, mixed $default = null): mixed
    {
        return data_get($this->configuration, $key, $default);
    }

    /**
     * 设置配置值
     */
    public function setConfig(string $key, mixed $value): void
    {
        $configuration = $this->configuration ?? [];
        data_set($configuration, $key, $value);
        $this->update(['configuration' => $configuration]);
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
     * 查询作用域：按能力筛选
     */
    public function scopeWithCapability($query, string $capability)
    {
        return $query->whereJsonContains('capabilities', $capability);
    }
}
