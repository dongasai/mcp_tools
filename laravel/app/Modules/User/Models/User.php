<?php

namespace App\Modules\User\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'timezone',
        'locale',
        'email_verified_at',
        'status',
        'role',
        'settings',
        'last_login_at',
        'last_login_ip',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'settings' => 'array',
        ];
    }

    /**
     * 用户状态常量
     */
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_PENDING = 'pending';

    /**
     * 用户角色常量
     */
    public const ROLE_SUPER_ADMIN = 'super_admin';
    public const ROLE_ADMIN = 'admin';
    public const ROLE_USER = 'user';

    /**
     * 获取用户的项目
     */
    public function projects(): HasMany
    {
        return $this->hasMany(\App\Modules\Project\Models\Project::class, 'user_id');
    }

    /**
     * 获取用户创建的任务
     */
    public function createdTasks(): HasMany
    {
        return $this->hasMany(\App\Modules\Task\Models\Task::class, 'created_by');
    }

    /**
     * 获取分配给用户的任务
     */
    public function assignedTasks(): HasMany
    {
        return $this->hasMany(\App\Modules\Task\Models\Task::class, 'assigned_to');
    }

    /**
     * 获取用户的Agent
     */
    public function agents(): HasMany
    {
        return $this->hasMany(\App\Modules\Agent\Models\Agent::class, 'user_id');
    }

    /**
     * 获取用户的GitHub连接
     */
    public function gitHubConnection(): HasOne
    {
        return $this->hasOne(\App\Modules\GitHub\Models\GitHubConnection::class, 'user_id');
    }

    /**
     * 获取用户的通知设置
     */
    public function notificationSettings(): HasOne
    {
        return $this->hasOne(\App\Modules\Notification\Models\NotificationSetting::class, 'user_id');
    }

    /**
     * 检查用户是否为超级管理员
     */
    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPER_ADMIN;
    }

    /**
     * 检查用户是否为管理员
     */
    public function isAdmin(): bool
    {
        return in_array($this->role, [self::ROLE_SUPER_ADMIN, self::ROLE_ADMIN]);
    }

    /**
     * 检查用户是否激活
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * 检查用户是否已验证邮箱
     */
    public function hasVerifiedEmail(): bool
    {
        return !is_null($this->email_verified_at);
    }

    /**
     * 获取用户显示名称
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->name ?: $this->email;
    }

    /**
     * 获取用户头像URL
     */
    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar) {
            return asset('storage/' . $this->avatar);
        }

        // 使用Gravatar作为默认头像
        $hash = md5(strtolower(trim($this->email)));
        return "https://www.gravatar.com/avatar/{$hash}?d=identicon&s=200";
    }

    /**
     * 获取用户时区
     */
    public function getTimezoneAttribute($value): string
    {
        return $value ?: config('app.timezone', 'UTC');
    }

    /**
     * 获取用户语言
     */
    public function getLocaleAttribute($value): string
    {
        return $value ?: config('app.locale', 'en');
    }

    /**
     * 更新最后登录信息
     */
    public function updateLastLogin(string $ip = null): void
    {
        $this->update([
            'last_login_at' => now(),
            'last_login_ip' => $ip ?: request()->ip(),
        ]);
    }

    /**
     * 获取用户设置
     */
    public function getSetting(string $key, mixed $default = null): mixed
    {
        return data_get($this->settings, $key, $default);
    }

    /**
     * 设置用户配置
     */
    public function setSetting(string $key, mixed $value): void
    {
        $settings = $this->settings ?: [];
        data_set($settings, $key, $value);
        $this->update(['settings' => $settings]);
    }

    /**
     * 检查用户权限
     */
    public function hasPermission(string $permission): bool
    {
        // 超级管理员拥有所有权限
        if ($this->isSuperAdmin()) {
            return true;
        }

        // 从设置中获取权限
        $permissions = $this->getSetting('permissions', []);
        return in_array($permission, $permissions);
    }

    /**
     * 获取用户统计信息
     */
    public function getStats(): array
    {
        return [
            'projects_count' => $this->projects()->count(),
            'created_tasks_count' => $this->createdTasks()->count(),
            'assigned_tasks_count' => $this->assignedTasks()->count(),
            'completed_tasks_count' => $this->assignedTasks()->where('status', 'completed')->count(),
            'agents_count' => $this->agents()->count(),
            'active_agents_count' => $this->agents()->where('status', 'active')->count(),
        ];
    }

    /**
     * 作用域：活跃用户
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * 作用域：已验证邮箱的用户
     */
    public function scopeVerified($query)
    {
        return $query->whereNotNull('email_verified_at');
    }

    /**
     * 作用域：管理员用户
     */
    public function scopeAdmins($query)
    {
        return $query->whereIn('role', [self::ROLE_SUPER_ADMIN, self::ROLE_ADMIN]);
    }
}
