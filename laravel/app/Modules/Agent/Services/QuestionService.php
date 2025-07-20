<?php

namespace App\Modules\Agent\Services;

use App\Modules\Agent\Models\Agent;
use App\Modules\Agent\Models\AgentQuestion;
use App\Modules\Agent\Events\QuestionCreated;
use App\Modules\Agent\Events\QuestionAnswered;
use App\Modules\Agent\Events\QuestionIgnored;
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
            'question_type' => $question->question_type,
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

        // 按类型统计
        foreach (AgentQuestion::getQuestionTypes() as $type => $label) {
            $stats['by_type'][$type] = (clone $query)->where('question_type', $type)->count();
        }

        return $stats;
    }

    /**
     * 获取高优先级待回答问题
     */
    public function getHighPriorityPendingQuestions(int $limit = 10): Collection
    {
        return AgentQuestion::pending()
            ->whereIn('priority', [AgentQuestion::PRIORITY_URGENT, AgentQuestion::PRIORITY_HIGH])
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
     * 批量更新问题状态
     */
    public function batchUpdateStatus(array $questionIds, string $status): int
    {
        if (!in_array($status, [AgentQuestion::STATUS_PENDING, AgentQuestion::STATUS_ANSWERED, AgentQuestion::STATUS_IGNORED])) {
            throw new \InvalidArgumentException("Invalid status: {$status}");
        }

        $updated = AgentQuestion::whereIn('id', $questionIds)->update([
            'status' => $status,
            'updated_at' => now(),
        ]);

        $this->logger->info('Batch status update', [
            'question_ids' => $questionIds,
            'status' => $status,
            'updated_count' => $updated,
        ]);

        return $updated;
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
        $required = ['agent_id', 'user_id', 'title', 'content', 'question_type'];
        
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                throw new \InvalidArgumentException("Required field '{$field}' is missing or empty");
            }
        }

        // 验证问题类型
        if (!in_array($data['question_type'], [AgentQuestion::TYPE_CHOICE, AgentQuestion::TYPE_FEEDBACK])) {
            throw new \InvalidArgumentException("Invalid question_type: {$data['question_type']}");
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

        if (isset($filters['question_type'])) {
            $query->where('question_type', $filters['question_type']);
        }

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
}
