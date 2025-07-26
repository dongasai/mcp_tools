<?php

namespace Modules\MCP\Controllers;

use App\Modules\Agent\Services\QuestionService;
use App\Modules\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class QuestionController extends BaseController
{
    public function __construct(
        private QuestionService $questionService
    ) {}

    /**
     * 获取问题列表
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'agent_id' => 'sometimes|integer|exists:agents,id',
            'user_id' => 'sometimes|integer|exists:users,id',
            'task_id' => 'sometimes|integer|exists:tasks,id',
            'project_id' => 'sometimes|integer|exists:projects,id',
            'status' => ['sometimes', Rule::in(['PENDING', 'ANSWERED', 'IGNORED'])],
            'priority' => ['sometimes', Rule::in(['URGENT', 'HIGH', 'MEDIUM', 'LOW'])],
            'question_type' => ['sometimes', Rule::in(['CHOICE', 'FEEDBACK'])],
            'per_page' => 'sometimes|integer|min:1|max:100',
        ]);

        $perPage = $validated['per_page'] ?? 15;
        unset($validated['per_page']);

        $questions = $this->questionService->getQuestions($validated, $perPage);

        return $this->success([
            'questions' => $questions->items(),
            'pagination' => [
                'total' => $questions->total(),
                'per_page' => $questions->perPage(),
                'current_page' => $questions->currentPage(),
                'last_page' => $questions->lastPage(),
                'from' => $questions->firstItem(),
                'to' => $questions->lastItem(),
            ],
        ]);
    }

    /**
     * 创建问题
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'agent_id' => 'required|integer|exists:agents,id',
            'user_id' => 'required|integer|exists:users,id',
            'task_id' => 'sometimes|integer|exists:tasks,id',
            'project_id' => 'sometimes|integer|exists:projects,id',
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'question_type' => ['required', Rule::in(['CHOICE', 'FEEDBACK'])],
            'priority' => ['sometimes', Rule::in(['URGENT', 'HIGH', 'MEDIUM', 'LOW'])],
            'context' => 'sometimes|array',
            'answer_options' => 'sometimes|array',
            'expires_in' => 'sometimes|integer|min:60|max:86400', // 1分钟到1天
        ]);

        try {
            $question = $this->questionService->createQuestion($validated);

            return $this->success([
                'question' => $question->load(['agent', 'user', 'task', 'project']),
                'message' => '问题创建成功',
            ], 201);

        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    /**
     * 获取问题详情
     */
    public function show(int $id): JsonResponse
    {
        $question = $this->questionService->getQuestionById($id);

        if (!$question) {
            return $this->error('问题不存在', 404);
        }

        return $this->success([
            'question' => $question,
        ]);
    }

    /**
     * 回答问题
     */
    public function answer(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'answer' => 'required|string',
            'answer_type' => ['sometimes', Rule::in(['TEXT', 'CHOICE', 'JSON', 'FILE'])],
            'answered_by' => 'sometimes|integer|exists:users,id',
        ]);

        $success = $this->questionService->answerQuestion(
            $id,
            $validated['answer'],
            $validated['answer_type'] ?? 'TEXT',
            $validated['answered_by'] ?? null
        );

        if (!$success) {
            return $this->error('回答问题失败', 400);
        }

        $question = $this->questionService->getQuestionById($id);

        return $this->success([
            'question' => $question,
            'message' => '问题回答成功',
        ]);
    }

    /**
     * 忽略问题
     */
    public function ignore(int $id): JsonResponse
    {
        $success = $this->questionService->ignoreQuestion($id);

        if (!$success) {
            return $this->error('忽略问题失败', 400);
        }

        $question = $this->questionService->getQuestionById($id);

        return $this->success([
            'question' => $question,
            'message' => '问题已忽略',
        ]);
    }

    /**
     * 删除问题
     */
    public function destroy(int $id): JsonResponse
    {
        $success = $this->questionService->deleteQuestion($id);

        if (!$success) {
            return $this->error('删除问题失败', 400);
        }

        return $this->success([
            'message' => '问题删除成功',
        ]);
    }

    /**
     * 获取问题统计
     */
    public function stats(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'agent_id' => 'sometimes|integer|exists:agents,id',
            'user_id' => 'sometimes|integer|exists:users,id',
            'task_id' => 'sometimes|integer|exists:tasks,id',
            'project_id' => 'sometimes|integer|exists:projects,id',
        ]);

        $stats = $this->questionService->getQuestionStats($validated);

        return $this->success([
            'stats' => $stats,
        ]);
    }

    /**
     * 获取Agent的问题列表
     */
    public function agentQuestions(Request $request, int $agentId): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['sometimes', Rule::in(['PENDING', 'ANSWERED', 'IGNORED'])],
            'priority' => ['sometimes', Rule::in(['URGENT', 'HIGH', 'MEDIUM', 'LOW'])],
            'question_type' => ['sometimes', Rule::in(['CHOICE', 'FEEDBACK'])],
            'per_page' => 'sometimes|integer|min:1|max:100',
        ]);

        $perPage = $validated['per_page'] ?? 15;
        unset($validated['per_page']);

        $questions = $this->questionService->getAgentQuestions($agentId, $validated, $perPage);

        return $this->success([
            'questions' => $questions->items(),
            'pagination' => [
                'total' => $questions->total(),
                'per_page' => $questions->perPage(),
                'current_page' => $questions->currentPage(),
                'last_page' => $questions->lastPage(),
                'from' => $questions->firstItem(),
                'to' => $questions->lastItem(),
            ],
        ]);
    }

    /**
     * 获取用户的问题列表
     */
    public function userQuestions(Request $request, int $userId): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['sometimes', Rule::in(['PENDING', 'ANSWERED', 'IGNORED'])],
            'priority' => ['sometimes', Rule::in(['URGENT', 'HIGH', 'MEDIUM', 'LOW'])],
            'question_type' => ['sometimes', Rule::in(['CHOICE', 'FEEDBACK'])],
            'per_page' => 'sometimes|integer|min:1|max:100',
        ]);

        $perPage = $validated['per_page'] ?? 15;
        unset($validated['per_page']);

        $questions = $this->questionService->getUserQuestions($userId, $validated, $perPage);

        return $this->success([
            'questions' => $questions->items(),
            'pagination' => [
                'total' => $questions->total(),
                'per_page' => $questions->perPage(),
                'current_page' => $questions->currentPage(),
                'last_page' => $questions->lastPage(),
                'from' => $questions->firstItem(),
                'to' => $questions->lastItem(),
            ],
        ]);
    }

    /**
     * 处理过期问题
     */
    public function processExpired(): JsonResponse
    {
        $processedCount = $this->questionService->processExpiredQuestions();

        return $this->success([
            'processed_count' => $processedCount,
            'message' => "已处理 {$processedCount} 个过期问题",
        ]);
    }
}
