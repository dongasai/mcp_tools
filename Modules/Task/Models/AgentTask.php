<?php

namespace Modules\Task\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Agent\Models\Agent;
use Modules\Task\Enums\TASKSTATUS;
use Modules\Task\Enums\TASKPRIORITY;

class AgentTask extends Model
{
    protected $table = 'agent_tasks';

    protected $fillable = [
        'agent_id',
        'main_task_id',
        'title',
        'description',
        'status',
        'type',
        'priority',
        'execution_data',
        'result_data',
        'started_at',
        'completed_at',
        'estimated_duration',
        'actual_duration',
        'retry_count',
        'max_retries',
        'error_message',
        'metadata',
    ];

    protected $casts = [
        'status' => 'string',
        'type' => 'string',
        'priority' => TASKPRIORITY::class,
        'execution_data' => 'array',
        'result_data' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'estimated_duration' => 'integer',
        'actual_duration' => 'integer',
        'retry_count' => 'integer',
        'max_retries' => 'integer',
        'metadata' => 'array',
    ];

    /**
     * 获取执行此任务的Agent
     */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    /**
     * 获取关联的主任务
     */
    public function mainTask(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'main_task_id');
    }

    /**
     * 查询范围：待执行的任务
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * 查询范围：执行中的任务
     */
    public function scopeRunning($query)
    {
        return $query->where('status', 'running');
    }

    /**
     * 查询范围：已完成的任务
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * 查询范围：失败的任务
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * 查询范围：重试中的任务
     */
    public function scopeRetrying($query)
    {
        return $query->where('status', 'retrying');
    }

    /**
     * 查询范围：按Agent筛选
     */
    public function scopeByAgent($query, $agentId)
    {
        return $query->where('agent_id', $agentId);
    }

    /**
     * 查询范围：按主任务筛选
     */
    public function scopeByMainTask($query, $mainTaskId)
    {
        return $query->where('main_task_id', $mainTaskId);
    }

    /**
     * 查询范围：按类型筛选
     */
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * 检查任务是否已完成
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * 检查任务是否失败
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * 检查任务是否正在运行
     */
    public function isRunning(): bool
    {
        return $this->status === 'running';
    }

    /**
     * 检查任务是否可以重试
     */
    public function canRetry(): bool
    {
        return $this->status === 'failed' && 
               $this->retry_count < $this->max_retries;
    }

    /**
     * 开始执行任务
     */
    public function start(): void
    {
        $this->update([
            'status' => 'running',
            'started_at' => now(),
        ]);
    }

    /**
     * 完成任务
     */
    public function complete(array $resultData = []): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'result_data' => $resultData,
            'actual_duration' => $this->started_at ? 
                now()->diffInSeconds($this->started_at) : null,
        ]);
    }

    /**
     * 标记任务失败
     */
    public function fail(string $errorMessage = ''): void
    {
        $this->update([
            'status' => 'failed',
            'completed_at' => now(),
            'error_message' => $errorMessage,
            'actual_duration' => $this->started_at ? 
                now()->diffInSeconds($this->started_at) : null,
        ]);
    }

    /**
     * 重试任务
     */
    public function retry(): void
    {
        if ($this->canRetry()) {
            $this->update([
                'status' => 'retrying',
                'retry_count' => $this->retry_count + 1,
                'error_message' => null,
            ]);
        }
    }

    /**
     * 取消任务
     */
    public function cancel(): void
    {
        $this->update([
            'status' => 'cancelled',
            'completed_at' => now(),
        ]);
    }

    /**
     * 获取执行时长（秒）
     */
    public function getExecutionDuration(): ?int
    {
        if ($this->started_at && $this->completed_at) {
            return $this->completed_at->diffInSeconds($this->started_at);
        }
        
        if ($this->started_at && $this->isRunning()) {
            return now()->diffInSeconds($this->started_at);
        }
        
        return null;
    }

    /**
     * 获取任务进度描述
     */
    public function getProgressDescription(): string
    {
        return match($this->status) {
            'pending' => '等待执行',
            'running' => '正在执行',
            'completed' => '执行完成',
            'failed' => '执行失败' . ($this->error_message ? ': ' . $this->error_message : ''),
            'retrying' => "重试中 ({$this->retry_count}/{$this->max_retries})",
            'cancelled' => '已取消',
            default => '未知状态',
        };
    }
}
