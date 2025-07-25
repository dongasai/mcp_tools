<?php

namespace App\Modules\MCP\Tools;

use App\Modules\Agent\Services\QuestionService;
use App\Modules\Agent\Services\QuestionAnalyticsService;
use App\Modules\Core\Contracts\LogInterface;
use Illuminate\Support\Facades\Validator;

/**
 * Agent问题批量操作工具
 * 
 * 提供批量处理、搜索和分析功能
 */
class QuestionBatchTool
{
    public function __construct(
        private QuestionService $questionService,
        private QuestionAnalyticsService $analyticsService,
        private LogInterface $logger
    ) {}

    /**
     * 获取工具信息
     */
    public function getInfo(): array
    {
        return [
            'name' => 'question_batch',
            'description' => 'Agent问题批量操作工具',
            'actions' => [
                'batch_update_status' => '批量更新问题状态',
                'batch_delete' => '批量删除问题',
                'search_questions' => '搜索问题',
                'get_analytics' => '获取问题分析报告',
                'extract_context' => '提取问题上下文',
            ],
        ];
    }

    /**
     * 批量更新问题状态
     */
    public function batchUpdateStatus(array $params): array
    {
        $validator = Validator::make($params, [
            'question_ids' => 'required|array|min:1',
            'question_ids.*' => 'integer|exists:agent_questions,id',
            'status' => 'required|string|in:PENDING,ANSWERED,IGNORED',
            'answer' => 'nullable|string|max:2000',
        ]);

        if ($validator->fails()) {
            return [
                'success' => false,
                'error' => 'Validation failed',
                'details' => $validator->errors()->toArray(),
            ];
        }

        try {
            $results = $this->questionService->batchUpdateStatus(
                $params['question_ids'],
                $params['status'],
                $params['answer'] ?? null
            );

            $successCount = count(array_filter($results, fn($r) => $r['status'] === 'success'));
            $errorCount = count($results) - $successCount;

            return [
                'success' => true,
                'message' => "Batch update completed: {$successCount} success, {$errorCount} errors",
                'results' => $results,
                'summary' => [
                    'total' => count($results),
                    'success' => $successCount,
                    'errors' => $errorCount,
                ],
            ];

        } catch (\Exception $e) {
            $this->logger->error('Batch update status failed', [
                'error' => $e->getMessage(),
                'params' => $params,
            ]);

            return [
                'success' => false,
                'error' => 'Batch update failed',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * 批量删除问题
     */
    public function batchDelete(array $params): array
    {
        $validator = Validator::make($params, [
            'question_ids' => 'required|array|min:1',
            'question_ids.*' => 'integer|exists:agent_questions,id',
        ]);

        if ($validator->fails()) {
            return [
                'success' => false,
                'error' => 'Validation failed',
                'details' => $validator->errors()->toArray(),
            ];
        }

        try {
            $results = $this->questionService->batchDelete($params['question_ids']);

            $successCount = count(array_filter($results, fn($r) => $r['status'] === 'success'));
            $errorCount = count($results) - $successCount;

            return [
                'success' => true,
                'message' => "Batch delete completed: {$successCount} success, {$errorCount} errors",
                'results' => $results,
                'summary' => [
                    'total' => count($results),
                    'success' => $successCount,
                    'errors' => $errorCount,
                ],
            ];

        } catch (\Exception $e) {
            $this->logger->error('Batch delete failed', [
                'error' => $e->getMessage(),
                'params' => $params,
            ]);

            return [
                'success' => false,
                'error' => 'Batch delete failed',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * 搜索问题
     */
    public function searchQuestions(array $params): array
    {
        $validator = Validator::make($params, [
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

        if ($validator->fails()) {
            return [
                'success' => false,
                'error' => 'Validation failed',
                'details' => $validator->errors()->toArray(),
            ];
        }

        try {
            $query = $params['query'] ?? '';
            $filters = $params['filters'] ?? [];
            $perPage = $params['per_page'] ?? 15;

            $results = $this->questionService->searchQuestions($query, $filters, $perPage);

            return [
                'success' => true,
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
            ];

        } catch (\Exception $e) {
            $this->logger->error('Search questions failed', [
                'error' => $e->getMessage(),
                'params' => $params,
            ]);

            return [
                'success' => false,
                'error' => 'Search failed',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * 获取问题分析报告
     */
    public function getAnalytics(array $params): array
    {
        $validator = Validator::make($params, [
            'filters' => 'nullable|array',
            'filters.agent_id' => 'nullable|integer|exists:agents,id',
            'filters.project_id' => 'nullable|integer|exists:projects,id',
            'filters.type' => 'nullable|string|in:CHOICE,FEEDBACK',
            'filters.priority' => 'nullable|string|in:URGENT,HIGH,MEDIUM,LOW',
            'filters.status' => 'nullable|string|in:PENDING,ANSWERED,IGNORED',
            'filters.date_from' => 'nullable|date',
            'filters.date_to' => 'nullable|date|after_or_equal:filters.date_from',
            'report_type' => 'nullable|string|in:overview,comprehensive,trends',
        ]);

        if ($validator->fails()) {
            return [
                'success' => false,
                'error' => 'Validation failed',
                'details' => $validator->errors()->toArray(),
            ];
        }

        try {
            $filters = $params['filters'] ?? [];
            $reportType = $params['report_type'] ?? 'overview';

            switch ($reportType) {
                case 'comprehensive':
                    $data = $this->analyticsService->getComprehensiveReport($filters);
                    break;
                    
                case 'trends':
                    $days = $params['days'] ?? 30;
                    $data = $this->analyticsService->getQuestionTrends($days, $filters);
                    break;
                    
                default: // overview
                    $data = [
                        'type_stats' => $this->analyticsService->getQuestionTypeStats($filters),
                        'status_stats' => $this->analyticsService->getQuestionStatusStats($filters),
                        'priority_stats' => $this->analyticsService->getQuestionPriorityStats($filters),
                        'response_time' => $this->analyticsService->getResponseTimeAnalysis($filters),
                    ];
                    break;
            }

            return [
                'success' => true,
                'report_type' => $reportType,
                'data' => $data,
                'filters_applied' => $filters,
                'generated_at' => now()->toISOString(),
            ];

        } catch (\Exception $e) {
            $this->logger->error('Get analytics failed', [
                'error' => $e->getMessage(),
                'params' => $params,
            ]);

            return [
                'success' => false,
                'error' => 'Analytics failed',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * 提取问题上下文
     */
    public function extractContext(array $params): array
    {
        $validator = Validator::make($params, [
            'question_id' => 'required|integer|exists:agent_questions,id',
        ]);

        if ($validator->fails()) {
            return [
                'success' => false,
                'error' => 'Validation failed',
                'details' => $validator->errors()->toArray(),
            ];
        }

        try {
            $question = $this->questionService->getQuestionById($params['question_id']);
            
            if (!$question) {
                return [
                    'success' => false,
                    'error' => 'Question not found',
                ];
            }

            $context = $this->questionService->extractContext($question);

            return [
                'success' => true,
                'question_id' => $params['question_id'],
                'context' => $context,
                'extracted_at' => now()->toISOString(),
            ];

        } catch (\Exception $e) {
            $this->logger->error('Extract context failed', [
                'error' => $e->getMessage(),
                'params' => $params,
            ]);

            return [
                'success' => false,
                'error' => 'Context extraction failed',
                'message' => $e->getMessage(),
            ];
        }
    }
}
