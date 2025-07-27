<?php

namespace Modules\Project\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\User\Models\User;

class ProjectMember extends Model
{
    protected $fillable = [
        'project_id',
        'user_id',
        'role',
        'permissions',
        'joined_at',
    ];

    protected $casts = [
        'permissions' => 'array',
        'joined_at' => 'datetime',
    ];

    /**
     * 角色常量
     */
    public const ROLE_OWNER = 'owner';
    public const ROLE_ADMIN = 'admin';
    public const ROLE_MEMBER = 'member';
    public const ROLE_VIEWER = 'viewer';

    /**
     * 权限常量
     */
    public const PERMISSION_READ = 'read';
    public const PERMISSION_WRITE = 'write';
    public const PERMISSION_DELETE = 'delete';
    public const PERMISSION_MANAGE_MEMBERS = 'manage_members';
    public const PERMISSION_MANAGE_SETTINGS = 'manage_settings';

    /**
     * 获取项目
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * 获取用户
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 检查是否有特定权限
     */
    public function hasPermission(string $permission): bool
    {
        // Owner和Admin拥有所有权限
        if ($this->isOwnerOrAdmin()) {
            return true;
        }

        $permissions = $this->permissions ?? [];
        return in_array($permission, $permissions);
    }

    /**
     * 检查是否为Owner或Admin
     */
    public function isOwnerOrAdmin(): bool
    {
        return in_array($this->role, [self::ROLE_OWNER, self::ROLE_ADMIN]);
    }

    /**
     * 检查是否为Owner
     */
    public function isOwner(): bool
    {
        return $this->role === self::ROLE_OWNER;
    }

    /**
     * 检查是否为Admin
     */
    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    /**
     * 检查是否为Member
     */
    public function isMember(): bool
    {
        return $this->role === self::ROLE_MEMBER;
    }

    /**
     * 检查是否为Viewer
     */
    public function isViewer(): bool
    {
        return $this->role === self::ROLE_VIEWER;
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
            default => '未知角色',
        };
    }

    /**
     * 获取默认权限（根据角色）
     */
    public static function getDefaultPermissions(string $role): array
    {
        return match($role) {
            self::ROLE_OWNER => [
                self::PERMISSION_READ,
                self::PERMISSION_WRITE,
                self::PERMISSION_DELETE,
                self::PERMISSION_MANAGE_MEMBERS,
                self::PERMISSION_MANAGE_SETTINGS,
            ],
            self::ROLE_ADMIN => [
                self::PERMISSION_READ,
                self::PERMISSION_WRITE,
                self::PERMISSION_DELETE,
                self::PERMISSION_MANAGE_MEMBERS,
            ],
            self::ROLE_MEMBER => [
                self::PERMISSION_READ,
                self::PERMISSION_WRITE,
            ],
            self::ROLE_VIEWER => [
                self::PERMISSION_READ,
            ],
            default => [],
        };
    }

    /**
     * 作用域：按项目查询
     */
    public function scopeByProject($query, $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    /**
     * 作用域：按用户查询
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * 作用域：按角色查询
     */
    public function scopeByRole($query, $role)
    {
        return $query->where('role', $role);
    }

    /**
     * 作用域：管理员及以上角色
     */
    public function scopeAdminAndAbove($query)
    {
        return $query->whereIn('role', [self::ROLE_OWNER, self::ROLE_ADMIN]);
    }
}
