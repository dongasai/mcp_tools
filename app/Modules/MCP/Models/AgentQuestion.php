<?php

namespace App\Modules\MCP\Models;

use App\Modules\MCP\Enums\QuestionPriority;
use App\Modules\MCP\Models\Agent;
use App\Modules\Task\Models\Task;
use App\Modules\Project\Models\Project;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class AgentQuestion extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'agent_id',
        'task_id',
        'project_id',
        'user_id',
        'title',
        'content',
        'context',
        'priority',
        'status',
        'answer',
        'answer_type',
        'answer_options',
        'answered_at',
        'answered_by',
        'expires_at',
    ];

    protected $casts = [
        'context' => 'array',
        'answer_options' => 'array',
        'priority' => QuestionPriority::class,
        'answered_at' => 'datetime',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // 问题类型已移除，默认为文本问题

    // 状态常量
    const STATUS_PENDING = 'PENDING';
    const STATUS_ANSWERED = 'ANSWERED';
    const STATUS_IGNORED = 'IGNORED';

    // 回答类型常量
    const ANSWER_TYPE_TEXT = 'TEXT';

    /**
     * 关联到Agent
     */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    /**
     * 关联到Task
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * 关联到Project
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * 关联到User（问题接收者）
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 关联到回答者
     */
    public function answeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'answered_by');
    }

    /**
     * 查询作用域：待回答的问题
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * 查询作用域：已回答的问题
     */
    public function scopeAnswered($query)
    {
        return $query->where('status', self::STATUS_ANSWERED);
    }

    /**
     * 查询作用域：已忽略的问题
     */
    public function scopeIgnored($query)
    {
        return $query->where('status', self::STATUS_IGNORED);
    }

    /**
     * 查询作用域：按优先级排序
     */
    public function scopeByPriority($query)
    {
        return $query->orderByRaw("CASE
            WHEN priority = '" . QuestionPriority::URGENT->value . "' THEN 1
            WHEN priority = '" . QuestionPriority::HIGH->value . "' THEN 2
            WHEN priority = '" . QuestionPriority::MEDIUM->value . "' THEN 3
            WHEN priority = '" . QuestionPriority::LOW->value . "' THEN 4
            ELSE 5
        END");
    }

    /**
     * 查询作用域：未过期的问题
     */
    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * 查询作用域：已过期的问题
     */
    public function scopeExpired($query)
    {
        return $query->whereNotNull('expires_at')
                    ->where('expires_at', '<=', now());
    }

    /**
     * 查询作用域：特定Agent的问题
     */
    public function scopeForAgent($query, $agentId)
    {
        return $query->where('agent_id', $agentId);
    }

    /**
     * 查询作用域：特定用户的问题
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * 查询作用域：特定任务的问题
     */
    public function scopeForTask($query, $taskId)
    {
        return $query->where('task_id', $taskId);
    }

    /**
     * 查询作用域：特定项目的问题
     */
    public function scopeForProject($query, $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    /**
     * 检查问题是否已过期
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * 检查问题是否待回答
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * 检查问题是否已回答
     */
    public function isAnswered(): bool
    {
        return $this->status === self::STATUS_ANSWERED;
    }

    /**
     * 检查问题是否已忽略
     */
    public function isIgnored(): bool
    {
        return $this->status === self::STATUS_IGNORED;
    }

    /**
     * 获取优先级权重
     */
    public function getPriorityWeight(): int
    {
        return $this->priority?->value() ?? 0;
    }

    /**
     * 获取优先级标签
     */
    public function getPriorityLabel(): string
    {
        return $this->priority?->label() ?? '未知';
    }

    /**
     * 获取状态标签
     */
    public function getStatusLabel(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => '待回答',
            self::STATUS_ANSWERED => '已回答',
            self::STATUS_IGNORED => '已忽略',
            default => '未知',
        };
    }

    /**
     * 获取问题类型标签（已废弃）
     */
    public function getTypeLabel(): string
    {
        return '文本问题';
    }

    /**
     * 标记问题为已回答
     */
    public function markAsAnswered(string $answer, ?string $answerType = null, ?int $answeredBy = null): bool
    {
        $this->answer = $answer;
        $this->answer_type = $answerType ?? self::ANSWER_TYPE_TEXT;
        $this->answered_by = $answeredBy;
        $this->answered_at = now();
        $this->status = self::STATUS_ANSWERED;

        return $this->save();
    }

    /**
     * 标记问题为已忽略
     */
    public function markAsIgnored(): bool
    {
        $this->status = self::STATUS_IGNORED;
        return $this->save();
    }

    /**
     * 获取所有优先级
     */
    public static function getPriorities(): array
    {
        return QuestionPriority::keyValuePairs();
    }

    /**
     * 获取所有状态
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_PENDING => '待回答',
            self::STATUS_ANSWERED => '已回答',
            self::STATUS_IGNORED => '已忽略',
        ];
    }
}
