<?php

namespace App\Modules\Project\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Modules\User\Models\User;

class ProjectMember extends Model
{

    protected $fillable = [
        'project_id',
        'user_id',
        'role',
        'joined_at',
        'permissions',
    ];

    protected $casts = [
        'joined_at' => 'datetime',
        'permissions' => 'array',
    ];

    /**
     * 角色常量
     */
    public const ROLE_OWNER = 'owner';
    public const ROLE_ADMIN = 'admin';
    public const ROLE_MEMBER = 'member';
    public const ROLE_VIEWER = 'viewer';

    /**
     * 关联项目
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * 关联用户
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 检查是否为项目所有者
     */
    public function isOwner(): bool
    {
        return $this->role === self::ROLE_OWNER;
    }

    /**
     * 检查是否为管理员
     */
    public function isAdmin(): bool
    {
        return in_array($this->role, [self::ROLE_OWNER, self::ROLE_ADMIN]);
    }

    /**
     * 检查是否有特定权限
     */
    public function hasPermission(string $permission): bool
    {
        if ($this->isOwner()) {
            return true;
        }

        $permissions = $this->permissions ?? [];
        return in_array($permission, $permissions);
    }

    /**
     * 获取角色显示名称
     */
    public function getRoleDisplayName(): string
    {
        return match($this->role) {
            self::ROLE_OWNER => '项目所有者',
            self::ROLE_ADMIN => '管理员',
            self::ROLE_MEMBER => '成员',
            self::ROLE_VIEWER => '查看者',
            default => $this->role,
        };
    }
}
