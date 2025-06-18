<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
