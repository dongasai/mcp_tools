<?php

namespace App\Modules\Mcp\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use App\Modules\User\Models\User;
use App\Modules\Project\Models\Project;
use App\Modules\Task\Models\Task;

class Agent extends Model
{
    // 状态常量
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_SUSPENDED = 'suspended';
    const STATUS_PENDING = 'pending';

    protected $fillable = [
        'identifier',
        'name',
        'description',
        'access_token',
        'capabilities',
        'configuration',
        'project_id',
        'allowed_actions',
        'status',
        'last_active_at',
        'token_expires_at',
        'user_id',
    ];

    protected $casts = [
        'capabilities' => 'array',
        'configuration' => 'array',
        'allowed_actions' => 'array',
        'last_active_at' => 'datetime',
        'token_expires_at' => 'datetime',
    ];

    protected $hidden = [
        'access_token',
    ];

    /**
     * 获取拥有此Agent的用户
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 获取此Agent所属的项目
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * 获取分配给此Agent的任务
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'agent_id', 'id');
    }

    /**
     * 获取此Agent的活跃任务
     */
    public function activeTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'agent_id', 'id')
                    ->whereIn('status', ['claimed', 'in_progress']);
    }

    /**
     * 查询范围：仅包含活跃的Agent
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * 查询范围：仅包含在线的Agent（最近5分钟内活跃）
     */
    public function scopeOnline($query)
    {
        return $query->where('status', 'active')
                    ->where('last_active_at', '>=', now()->subMinutes(5));
    }

    /**
     * 检查Agent是否有权限访问项目
     */
    public function canAccessProject(int $projectId): bool
    {
        return $this->project_id === $projectId;
    }

    /**
     * 检查Agent是否有权限执行操作
     */
    public function canPerformAction(string $action): bool
    {
        $allowedActions = $this->allowed_actions ?? [];
        return in_array($action, $allowedActions);
    }

    /**
     * 为Agent生成新的访问令牌
     */
    public function generateAccessToken(): string
    {
        $token = 'mcp_token_' . Str::random(40);
        $this->access_token = $token;
        $this->token_expires_at = now()->addSeconds(config('mcp.access_control.token_expiry', 86400));
        $this->save();

        return $token;
    }

    /**
     * 检查Agent的令牌是否已过期
     */
    public function isTokenExpired(): bool
    {
        return $this->token_expires_at && $this->token_expires_at->isPast();
    }

    /**
     * 更新Agent的最后活跃时间戳
     */
    public function updateLastActive(): void
    {
        $this->last_active_at = now();
        $this->save();
    }

    /**
     * 获取此Agent可以访问的项目
     */
    public function accessibleProjects()
    {
        if (!$this->project_id) {
            return collect();
        }

        return collect([$this->project]);
    }
}
