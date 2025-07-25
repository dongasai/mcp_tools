<?php

namespace App\Modules\Mcp\Services;

use App\Modules\Mcp\Enums\QuestionPriority;
use App\Modules\Mcp\Models\Agent;
use App\Modules\Mcp\Models\AgentQuestion;
use App\Modules\Mcp\Events\QuestionCreated;
use App\Modules\Mcp\Events\QuestionAnswered;
use App\Modules\Mcp\Events\QuestionIgnored;
use App\Modules\Core\Contracts\LogInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Carbon\Carbon;

class QuestionService
{
    public function __construct(
        private LogInterface $logger
    ) {}

    /**
     * 创建问题
     */
    public function createQuestion(array $data): AgentQuestion
    {
        // 验证必需字段
        $this->validateQuestionData($data);

        // 设置过期时间
        if (isset($data['expires_in']) && $data['expires_in'] > 0) {
            $data['expires_at'] = now()->addSeconds($data['expires_in']);
            unset($data['expires_in']);
        }

        // 创建问题
        $question = AgentQuestion::create($data);

        $this->logger->info('Agent question created', [
            'question_id' => $question->id,
            'agent_id' => $question->agent_id,
            'user_id' => $question->user_id,
            'priority' => $question->priority,
        ]);

        // 触发问题创建事件
        QuestionCreated::dispatch($question);

        return $question;
    }

    /**
     * 获取问题列表
     */
    public function getQuestions(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = AgentQuestion::query()->with(['agent', 'user', 'task', 'project', 'answeredBy']);

        // 应用过滤条件
        $this->applyFilters($query, $filters);

        // 应用排序
        $this->applySorting($query, $filters);

        return $query->paginate($perPage);
    }

    /**
     * 获取Agent的问题列表
     */
    public function getAgentQuestions(int $agentId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $filters['agent_id'] = $agentId;
        return $this->getQuestions($filters, $perPage);
    }

    /**
     * 获取用户的问题列表
     */
    public function getUserQuestions(int $userId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $filters['user_id'] = $userId;
        return $this->getQuestions($filters, $perPage);
    }

    /**
     * 获取待回答的问题
     */
    public function getPendingQuestions(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $filters['status'] = AgentQuestion::STATUS_PENDING;
        return $this->getQuestions($filters, $perPage);
    }

    /**
     * 根据ID获取问题
     */
    public function getQuestionById(int $questionId): ?AgentQuestion
    {
        return AgentQuestion::with(['agent', 'user', 'task', 'project', 'answeredBy'])
                           ->find($questionId);
    }

    /**
     * 回答问题
     */
    public function answerQuestion(int $questionId, string $answer, ?string $answerType = null, ?int $answeredBy = null): bool
    {
        $question = $this->getQuestionById($questionId);
        
        if (!$question) {
            $this->logger->warning('Question not found for answering', ['question_id' => $questionId]);
            return false;
        }

        if (!$question->isPending()) {
            $this->logger->warning('Question is not pending', [
                'question_id' => $questionId,
                'current_status' => $question->status
            ]);
            return false;
        }

        $success = $question->markAsAnswered($answer, $answerType, $answeredBy);

        if ($success) {
            $this->logger->info('Question answered', [
                'question_id' => $questionId,
                'answered_by' => $answeredBy,
                'answer_type' => $answerType,
            ]);

            // 触发问题回答事件
            QuestionAnswered::dispatch($question);
        }

        return $success;
    }

    /**
     * 忽略问题
     */
    public function ignoreQuestion(int $questionId): bool
    {
        $question = $this->getQuestionById($questionId);
        
        if (!$question) {
            $this->logger->warning('Question not found for ignoring', ['question_id' => $questionId]);
            return false;
        }

        if (!$question->isPending()) {
            $this->logger->warning('Question is not pending', [
                'question_id' => $questionId,
                'current_status' => $question->status
            ]);
            return false;
        }

        $success = $question->markAsIgnored();

        if ($success) {
            $this->logger->info('Question ignored', [
                'question_id' => $questionId,
            ]);

            // 触发问题忽略事件
            QuestionIgnored::dispatch($question);
        }

        return $success;
    }

    /**
     * 处理过期问题
     */
    public function processExpiredQuestions(): int
    {
        $expiredQuestions = AgentQuestion::pending()
                                       ->expired()
                                       ->get();

        $processedCount = 0;

        foreach ($expiredQuestions as $question) {
            if ($question->markAsIgnored()) {
                $processedCount++;
                
                $this->logger->info('Expired question auto-ignored', [
                    'question_id' => $question->id,
                    'agent_id' => $question->agent_id,
                    'expired_at' => $question->expires_at,
                ]);
            }
        }

        if ($processedCount > 0) {
            $this->logger->info('Processed expired questions', [
                'processed_count' => $processedCount,
            ]);
        }

        return $processedCount;
    }

    /**
     * 获取问题统计
     */
    public function getQuestionStats(array $filters = []): array
    {
        $query = AgentQuestion::query();
        $this->applyFilters($query, $filters);

        $stats = [
            'total' => $query->count(),
            'pending' => (clone $query)->pending()->count(),
            'answered' => (clone $query)->answered()->count(),
            'ignored' => (clone $query)->ignored()->count(),
            'expired' => (clone $query)->pending()->expired()->count(),
            'by_priority' => [],
            'by_type' => [],
        ];

        // 按优先级统计
        foreach (AgentQuestion::getPriorities() as $priority => $label) {
            $stats['by_priority'][$priority] = (clone $query)->where('priority', $priority)->count();
        }

        // 问题类型统计已移除（问题类型已简化）

        return $stats;
    }

    /**
     * 获取高优先级待回答问题
     */
    public function getHighPriorityPendingQuestions(int $limit = 10): Collection
    {
        return AgentQuestion::pending()
            ->whereIn('priority', [QuestionPriority::URGENT->value, QuestionPriority::HIGH->value])
            ->notExpired()
            ->byPriority()
            ->limit($limit)
            ->get();
    }

    /**
     * 获取即将过期的问题
     */
    public function getExpiringQuestions(int $minutesUntilExpiry = 30): Collection
    {
        return AgentQuestion::pending()
            ->whereNotNull('expires_at')
            ->whereBetween('expires_at', [
                now(),
                now()->addMinutes($minutesUntilExpiry)
            ])
            ->byPriority()
            ->get();
    }

    /**
     * 获取用户的待回答问题数量
     */
    public function getUserPendingCount(int $userId): int
    {
        return AgentQuestion::pending()
            ->forUser($userId)
            ->notExpired()
            ->count();
    }

    /**
     * 获取Agent的问题统计
     */
    public function getAgentQuestionStats(int $agentId): array
    {
        $baseQuery = AgentQuestion::forAgent($agentId);

        return [
            'total' => (clone $baseQuery)->count(),
            'pending' => (clone $baseQuery)->pending()->count(),
            'answered' => (clone $baseQuery)->answered()->count(),
            'ignored' => (clone $baseQuery)->ignored()->count(),
            'expired' => (clone $baseQuery)->pending()->expired()->count(),
            'avg_response_time' => $this->getAverageResponseTime($agentId),
        ];
    }

    /**
     * 获取平均回答时间（分钟）
     */
    public function getAverageResponseTime(int $agentId): ?float
    {
        $answeredQuestions = AgentQuestion::forAgent($agentId)
            ->answered()
            ->whereNotNull('answered_at')
            ->get();

        if ($answeredQuestions->isEmpty()) {
            return null;
        }

        $totalMinutes = $answeredQuestions->sum(function ($question) {
            return $question->created_at->diffInMinutes($question->answered_at);
        });

        return round($totalMinutes / $answeredQuestions->count(), 2);
    }

    /**
     * 批量更新问题状态（增强版）
     */
    public function batchUpdateStatus(array $questionIds, string $status, ?string $answer = null): array
    {
        $validStatuses = [AgentQuestion::STATUS_PENDING, AgentQuestion::STATUS_ANSWERED, AgentQuestion::STATUS_IGNORED];
        if (!in_array($status, $validStatuses)) {
            throw new \InvalidArgumentException("Invalid status: {$status}");
        }

        $questions = AgentQuestion::whereIn('id', $questionIds)->get();
        $results = [];

        foreach ($questions as $question) {
            try {
                $oldStatus = $question->status;

                $question->status = $status;

                // 根据状态设置相应字段
                if ($status === AgentQuestion::STATUS_ANSWERED) {
                    if ($answer) {
                        $question->answer = $answer;
                    }
                    $question->answered_at = now();
                } elseif ($status === AgentQuestion::STATUS_IGNORED) {
                    $question->ignored_at = now();
                }

                $question->save();

                // 触发相应事件
                if ($status === AgentQuestion::STATUS_ANSWERED && $oldStatus !== AgentQuestion::STATUS_ANSWERED) {
                    QuestionAnswered::dispatch($question);
                } elseif ($status === AgentQuestion::STATUS_IGNORED && $oldStatus !== AgentQuestion::STATUS_IGNORED) {
                    QuestionIgnored::dispatch($question);
                }

                $results[] = [
                    'id' => $question->id,
                    'status' => 'success',
                    'old_status' => $oldStatus,
                    'new_status' => $status,
                ];

                $this->logger->info('Question status updated in batch', [
                    'question_id' => $question->id,
                    'old_status' => $oldStatus,
                    'new_status' => $status,
                ]);

            } catch (\Exception $e) {
                $results[] = [
                    'id' => $question->id,
                    'status' => 'error',
                    'error' => $e->getMessage(),
                ];

                $this->logger->error('Failed to update question status in batch', [
                    'question_id' => $question->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    /**
     * 删除问题
     */
    public function deleteQuestion(int $questionId): bool
    {
        $question = $this->getQuestionById($questionId);

        if (!$question) {
            return false;
        }

        $success = $question->delete();

        if ($success) {
            $this->logger->info('Question deleted', [
                'question_id' => $questionId,
            ]);
        }

        return $success;
    }

    /**
     * 验证问题数据
     */
    private function validateQuestionData(array $data): void
    {
        $required = ['agent_id', 'user_id', 'title', 'content'];

        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                throw new \InvalidArgumentException("Required field '{$field}' is missing or empty");
            }
        }

        // 验证优先级
        if (isset($data['priority']) && !in_array($data['priority'], array_keys(AgentQuestion::getPriorities()))) {
            throw new \InvalidArgumentException("Invalid priority: {$data['priority']}");
        }
    }

    /**
     * 应用查询过滤条件
     */
    private function applyFilters($query, array $filters): void
    {
        if (isset($filters['agent_id'])) {
            $query->forAgent($filters['agent_id']);
        }

        if (isset($filters['user_id'])) {
            $query->forUser($filters['user_id']);
        }

        if (isset($filters['task_id'])) {
            $query->forTask($filters['task_id']);
        }

        if (isset($filters['project_id'])) {
            $query->forProject($filters['project_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        // question_type 过滤已移除（问题类型已简化）

        if (isset($filters['not_expired']) && $filters['not_expired']) {
            $query->notExpired();
        }

        if (isset($filters['expired']) && $filters['expired']) {
            $query->expired();
        }
    }

    /**
     * 应用排序条件
     */
    private function applySorting($query, array $filters): void
    {
        $sortBy = $filters['sort_by'] ?? 'priority';
        $sortOrder = $filters['sort_order'] ?? 'desc';

        switch ($sortBy) {
            case 'priority':
                $query->byPriority();
                if ($sortOrder === 'asc') {
                    // 如果需要升序，再次排序
                    $query->orderByRaw("CASE
                        WHEN priority = 'LOW' THEN 4
                        WHEN priority = 'MEDIUM' THEN 3
                        WHEN priority = 'HIGH' THEN 2
                        WHEN priority = 'URGENT' THEN 1
                        ELSE 0
                    END ASC");
                }
                // 次要排序：创建时间
                $query->latest();
                break;

            case 'created_at':
                $query->orderBy('created_at', $sortOrder);
                break;

            case 'expires_at':
                $query->orderBy('expires_at', $sortOrder);
                break;

            case 'answered_at':
                $query->orderBy('answered_at', $sortOrder);
                break;

            case 'status':
                $query->orderByRaw("CASE
                    WHEN status = 'PENDING' THEN 1
                    WHEN status = 'ANSWERED' THEN 2
                    WHEN status = 'IGNORED' THEN 3
                    ELSE 4
                END " . strtoupper($sortOrder));
                break;

            default:
                // 默认排序：优先级 + 创建时间
                $query->byPriority()->latest();
                break;
        }
    }



    /**
     * 批量删除问题
     */
    public function batchDelete(array $questionIds): array
    {
        $questions = AgentQuestion::whereIn('id', $questionIds)->get();
        $results = [];

        foreach ($questions as $question) {
            try {
                $question->delete();

                $results[] = [
                    'id' => $question->id,
                    'status' => 'success',
                ];

                $this->logger->info('Question deleted in batch', [
                    'question_id' => $question->id,
                ]);

            } catch (\Exception $e) {
                $results[] = [
                    'id' => $question->id,
                    'status' => 'error',
                    'error' => $e->getMessage(),
                ];

                $this->logger->error('Failed to delete question in batch', [
                    'question_id' => $question->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    /**
     * 搜索问题
     */
    public function searchQuestions(string $query, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $searchQuery = AgentQuestion::query()->with(['agent', 'user']);

        // 全文搜索
        if (!empty($query)) {
            $searchQuery->where(function ($q) use ($query) {
                $q->where('title', 'LIKE', "%{$query}%")
                  ->orWhere('content', 'LIKE', "%{$query}%")
                  ->orWhere('answer', 'LIKE', "%{$query}%")
                  ->orWhere('context', 'LIKE', "%{$query}%")
                  ->orWhereHas('agent', function ($agentQuery) use ($query) {
                      $agentQuery->where('name', 'LIKE', "%{$query}%")
                                 ->orWhere('agent_id', 'LIKE', "%{$query}%");
                  });
            });
        }

        // 应用过滤条件
        $this->applySearchFilters($searchQuery, $filters);

        // 排序
        $sortBy = $filters['sort_by'] ?? 'priority';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $this->applySorting($searchQuery, ['sort_by' => $sortBy, 'sort_order' => $sortOrder]);

        return $searchQuery->paginate($perPage);
    }

    /**
     * 提取问题上下文信息
     */
    public function extractContext(AgentQuestion $question): array
    {
        $context = [];

        // 基础上下文
        $context['question_id'] = $question->id;
        $context['agent_info'] = [
            'id' => $question->agent->id,
            'name' => $question->agent->name,
            'agent_id' => $question->agent->agent_id,
            'type' => $question->agent->type,
        ];

        // 项目上下文
        if ($question->project_id) {
            $context['project_info'] = [
                'id' => $question->project_id,
                // 可以添加更多项目信息
            ];
        }

        // 相关问题
        $relatedQuestions = AgentQuestion::where('agent_id', $question->agent_id)
            ->where('id', '!=', $question->id)
            ->where('created_at', '>=', $question->created_at->subHours(24))
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get(['id', 'title', 'content', 'priority', 'status', 'created_at']);

        $context['related_questions'] = $relatedQuestions->toArray();

        // 问题模式分析
        $context['patterns'] = $this->analyzeQuestionPatterns($question);

        // 时间上下文
        $context['timing'] = [
            'created_at' => $question->created_at->toISOString(),
            'expires_at' => $question->expires_at?->toISOString(),
            'time_since_creation' => $question->created_at->diffForHumans(),
            'time_until_expiry' => $question->expires_at?->diffForHumans(),
        ];

        return $context;
    }

    /**
     * 分析问题模式
     */
    private function analyzeQuestionPatterns(AgentQuestion $question): array
    {
        $patterns = [];

        // 分析Agent的提问频率
        $recentQuestions = AgentQuestion::where('agent_id', $question->agent_id)
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        $patterns['recent_question_frequency'] = $recentQuestions;

        // 问题类型偏好分析已移除（问题类型已简化）

        // 分析优先级模式
        $priorityStats = AgentQuestion::where('agent_id', $question->agent_id)
            ->selectRaw('priority, COUNT(*) as count')
            ->groupBy('priority')
            ->pluck('count', 'priority')
            ->toArray();

        $patterns['priority_patterns'] = $priorityStats;

        return $patterns;
    }

    /**
     * 应用搜索过滤条件
     */
    private function applySearchFilters($query, array $filters): void
    {
        if (isset($filters['agent_id'])) {
            $query->where('agent_id', $filters['agent_id']);
        }

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['project_id'])) {
            $query->where('project_id', $filters['project_id']);
        }

        // type 和 question_type 过滤已移除（问题类型已简化）

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        if (isset($filters['has_answer'])) {
            if ($filters['has_answer']) {
                $query->whereNotNull('answer');
            } else {
                $query->whereNull('answer');
            }
        }

        if (isset($filters['is_expired'])) {
            if ($filters['is_expired']) {
                $query->where('expires_at', '<', now());
            } else {
                $query->where(function ($q) {
                    $q->whereNull('expires_at')
                      ->orWhere('expires_at', '>=', now());
                });
            }
        }
    }
}
