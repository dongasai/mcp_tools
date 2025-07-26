<?php

namespace Modules\MCP\Controllers;

use App\Modules\Agent\Services\QuestionService;
use App\Modules\Agent\Services\QuestionAnalyticsService;
use App\Modules\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Agent问题分析控制器
 */
class QuestionAnalyticsController extends BaseController
{
    public function __construct(
        private QuestionService $questionService,
        private QuestionAnalyticsService $analyticsService
    ) {}

    /**
     * 批量更新问题状态
     */
    public function batchUpdateStatus(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'question_ids' => 'required|array|min:1',
            'question_ids.*' => 'integer|exists:agent_questions,id',
            'status' => 'required|string|in:PENDING,ANSWERED,IGNORED',
            'answer' => 'nullable|string|max:2000',
        ]);

        try {
            $results = $this->questionService->batchUpdateStatus(
                $validated['question_ids'],
                $validated['status'],
                $validated['answer'] ?? null
            );

            $successCount = count(array_filter($results, fn($r) => $r['status'] === 'success'));
            $errorCount = count($results) - $successCount;

            return $this->success([
                'message' => "Batch update completed: {$successCount} success, {$errorCount} errors",
                'results' => $results,
                'summary' => [
                    'total' => count($results),
                    'success' => $successCount,
                    'errors' => $errorCount,
                ],
            ]);

        } catch (\Exception $e) {
            return $this->error('Batch update failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 批量删除问题
     */
    public function batchDelete(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'question_ids' => 'required|array|min:1',
            'question_ids.*' => 'integer|exists:agent_questions,id',
        ]);

        try {
            $results = $this->questionService->batchDelete($validated['question_ids']);

            $successCount = count(array_filter($results, fn($r) => $r['status'] === 'success'));
            $errorCount = count($results) - $successCount;

            return $this->success([
                'message' => "Batch delete completed: {$successCount} success, {$errorCount} errors",
                'results' => $results,
                'summary' => [
                    'total' => count($results),
                    'success' => $successCount,
                    'errors' => $errorCount,
                ],
            ]);

        } catch (\Exception $e) {
            return $this->error('Batch delete failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 搜索问题
     */
    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'query' => 'nullable|string|max:500',
            'filters' => 'nullable|array',
            'filters.agent_id' => 'nullable|integer|exists:agents,id',
            'filters.user_id' => 'nullable|integer|exists:users,id',
            'filters.project_id' => 'nullable|integer|exists:projects,id',
            'filters.type' => 'nullable|string|in:CHOICE,FEEDBACK',
            'filters.status' => 'nullable|string|in:PENDING,ANSWERED,IGNORED',
            'filters.priority' => 'nullable|string|in:URGENT,HIGH,MEDIUM,LOW',
            'filters.date_from' => 'nullable|date',
            'filters.date_to' => 'nullable|date|after_or_equal:filters.date_from',
            'filters.has_answer' => 'nullable|boolean',
            'filters.is_expired' => 'nullable|boolean',
            'filters.sort_by' => 'nullable|string|in:priority,created_at,expires_at,answered_at,status',
            'filters.sort_order' => 'nullable|string|in:asc,desc',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        try {
            $query = $validated['query'] ?? '';
            $filters = $validated['filters'] ?? [];
            $perPage = $validated['per_page'] ?? 15;

            $results = $this->questionService->searchQuestions($query, $filters, $perPage);

            return $this->success([
                'data' => $results->items(),
                'pagination' => [
                    'current_page' => $results->currentPage(),
                    'last_page' => $results->lastPage(),
                    'per_page' => $results->perPage(),
                    'total' => $results->total(),
                    'from' => $results->firstItem(),
                    'to' => $results->lastItem(),
                ],
                'search_info' => [
                    'query' => $query,
                    'filters_applied' => count($filters),
                    'total_results' => $results->total(),
                ],
            ]);

        } catch (\Exception $e) {
            return $this->error('Search failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 获取问题类型统计
     */
    public function getTypeStats(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'agent_id' => 'nullable|integer|exists:agents,id',
            'project_id' => 'nullable|integer|exists:projects,id',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        try {
            $stats = $this->analyticsService->getQuestionTypeStats($filters);
            return $this->success($stats);
        } catch (\Exception $e) {
            return $this->error('Failed to get type stats: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 获取问题状态统计
     */
    public function getStatusStats(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'agent_id' => 'nullable|integer|exists:agents,id',
            'project_id' => 'nullable|integer|exists:projects,id',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        try {
            $stats = $this->analyticsService->getQuestionStatusStats($filters);
            return $this->success($stats);
        } catch (\Exception $e) {
            return $this->error('Failed to get status stats: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 获取回答时间分析
     */
    public function getResponseTimeAnalysis(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'agent_id' => 'nullable|integer|exists:agents,id',
            'project_id' => 'nullable|integer|exists:projects,id',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        try {
            $analysis = $this->analyticsService->getResponseTimeAnalysis($filters);
            return $this->success($analysis);
        } catch (\Exception $e) {
            return $this->error('Failed to get response time analysis: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 获取Agent提问模式分析
     */
    public function getAgentPatterns(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'project_id' => 'nullable|integer|exists:projects,id',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'limit' => 'nullable|integer|min:1|max:50',
        ]);

        try {
            $patterns = $this->analyticsService->getAgentQuestionPatterns($filters);
            
            $limit = $filters['limit'] ?? 20;
            $limitedPatterns = $patterns->take($limit);

            return $this->success([
                'patterns' => $limitedPatterns,
                'total_agents' => $patterns->count(),
                'showing' => $limitedPatterns->count(),
            ]);
        } catch (\Exception $e) {
            return $this->error('Failed to get agent patterns: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 获取问题趋势分析
     */
    public function getTrends(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'days' => 'nullable|integer|min:1|max:365',
            'agent_id' => 'nullable|integer|exists:agents,id',
            'project_id' => 'nullable|integer|exists:projects,id',
        ]);

        try {
            $days = $validated['days'] ?? 30;
            $filters = array_filter($validated, fn($key) => $key !== 'days', ARRAY_FILTER_USE_KEY);
            
            $trends = $this->analyticsService->getQuestionTrends($days, $filters);
            
            return $this->success([
                'trends' => $trends,
                'period' => [
                    'days' => $days,
                    'from' => now()->subDays($days)->toDateString(),
                    'to' => now()->toDateString(),
                ],
                'total_days' => $trends->count(),
            ]);
        } catch (\Exception $e) {
            return $this->error('Failed to get trends: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 获取综合分析报告
     */
    public function getComprehensiveReport(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'agent_id' => 'nullable|integer|exists:agents,id',
            'project_id' => 'nullable|integer|exists:projects,id',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        try {
            $report = $this->analyticsService->getComprehensiveReport($filters);
            return $this->success($report);
        } catch (\Exception $e) {
            return $this->error('Failed to get comprehensive report: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 提取问题上下文
     */
    public function extractContext(Request $request, int $questionId): JsonResponse
    {
        try {
            $question = $this->questionService->getQuestionById($questionId);
            
            if (!$question) {
                return $this->error('Question not found', 404);
            }

            $context = $this->questionService->extractContext($question);

            return $this->success([
                'question_id' => $questionId,
                'context' => $context,
                'extracted_at' => now()->toISOString(),
            ]);

        } catch (\Exception $e) {
            return $this->error('Failed to extract context: ' . $e->getMessage(), 500);
        }
    }
}
