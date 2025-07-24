<?php

namespace App\Modules\Project\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Modules\User\Models\User;
use App\Modules\Task\Models\Task;
use App\Modules\Agent\Models\Agent;

class Project extends Model
{
    protected $fillable = [
        'name',
        'description',
        'timezone',
        'status',
        'repositories',
        'settings',
        'user_id',
    ];

    protected $casts = [
        'repositories' => 'array',
        'settings' => 'array',
    ];

    /**
     * 获取拥有此项目的用户
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 获取项目的任务
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * 获取项目的Agent
     */
    public function agents(): HasMany
    {
        return $this->hasMany(Agent::class);
    }

    /**
     * 获取项目的活跃任务
     */
    public function activeTasks(): HasMany
    {
        return $this->hasMany(Task::class)->whereIn('status', ['pending', 'claimed', 'in_progress']);
    }

    /**
     * 获取项目的已完成任务
     */
    public function completedTasks(): HasMany
    {
        return $this->hasMany(Task::class)->where('status', 'completed');
    }

    /**
     * 获取项目成员
     */
    public function members(): HasMany
    {
        return $this->hasMany(ProjectMember::class);
    }

    /**
     * 获取项目成员（包含用户信息）
     */
    public function membersWithUsers(): HasMany
    {
        return $this->hasMany(ProjectMember::class)->with('user');
    }

    /**
     * 获取项目管理员（Owner和Admin）
     */
    public function admins(): HasMany
    {
        return $this->hasMany(ProjectMember::class)->adminAndAbove();
    }

    /**
     * 检查用户是否为项目成员
     */
    public function hasMember(User $user): bool
    {
        return $this->members()->where('user_id', $user->id)->exists();
    }

    /**
     * 获取用户在项目中的角色
     */
    public function getUserRole(User $user): ?string
    {
        $member = $this->members()->where('user_id', $user->id)->first();
        return $member?->role;
    }

    /**
     * 检查用户是否为项目所有者
     */
    public function isOwner(User $user): bool
    {
        return $this->getUserRole($user) === ProjectMember::ROLE_OWNER;
    }

    /**
     * 检查用户是否为项目管理员（Owner或Admin）
     */
    public function isAdmin(User $user): bool
    {
        $role = $this->getUserRole($user);
        return in_array($role, [ProjectMember::ROLE_OWNER, ProjectMember::ROLE_ADMIN]);
    }

    /**
     * 查询范围：仅包含活跃的项目
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * 获取项目的GitHub仓库
     */
    public function getRepositoriesAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * 设置项目的GitHub仓库
     */
    public function setRepositoriesAttribute($value)
    {
        $this->attributes['repositories'] = json_encode($value);
    }
}
