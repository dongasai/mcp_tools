<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Task extends Model
{
    protected $fillable = [
        'title',
        'description',
        'status',
        'priority',
        'labels',
        'due_date',
        'solution',
        'time_spent',
        'github_issue_url',
        'github_issue_number',
        'project_id',
        'assigned_to',
        'agent_id',
    ];

    protected $casts = [
        'labels' => 'array',
        'due_date' => 'datetime',
    ];

    /**
     * 获取拥有此任务的项目
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * 获取分配给此任务的用户
     */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * 获取分配给此任务的Agent
     */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'agent_id', 'id');
    }

    /**
     * 查询范围：仅包含待处理的任务
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * 查询范围：仅包含已认领的任务
     */
    public function scopeClaimed($query)
    {
        return $query->where('status', 'claimed');
    }

    /**
     * 查询范围：仅包含已完成的任务
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * 查询范围：按优先级筛选
     */
    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * 查询范围：按Agent筛选
     */
    public function scopeByAgent($query, $agentId)
    {
        return $query->where('agent_id', $agentId);
    }

    /**
     * 检查任务是否已过期
     */
    public function isOverdue(): bool
    {
        return $this->due_date && $this->due_date->isPast() && !in_array($this->status, ['completed', 'cancelled']);
    }

    /**
     * 获取任务的标签
     */
    public function getLabelsAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * 设置任务的标签
     */
    public function setLabelsAttribute($value)
    {
        $this->attributes['labels'] = json_encode($value);
    }
}
