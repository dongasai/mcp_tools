<?php

namespace Modules\Task\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Task\Models\Task;
use Modules\Task\Models\TaskComment;
use Modules\Task\Services\TaskCommentService;
use App\Modules\User\Models\User;
use App\Modules\Agent\Models\Agent;

class TaskCommentController extends Controller
{
    protected TaskCommentService $commentService;

    public function __construct(TaskCommentService $commentService)
    {
        $this->commentService = $commentService;
    }

    /**
     * 获取任务评论列表
     */
    public function index(Request $request, Task $task): JsonResponse
    {
        try {
            $filters = [
                'comment_type' => $request->get('comment_type'),
                'is_internal' => $request->boolean('is_internal'),
                'author_type' => $request->get('author_type'),
                'include_replies' => $request->boolean('include_replies', true),
                'sort_order' => $request->get('sort_order', 'asc'),
            ];

            $page = $request->get('page', 1);
            $perPage = min($request->get('per_page', 20), 100);

            $comments = $this->commentService->getTaskComments($task, $filters, $page, $perPage);

            return response()->json([
                'success' => true,
                'data' => $comments->items(),
                'pagination' => [
                    'current_page' => $comments->currentPage(),
                    'last_page' => $comments->lastPage(),
                    'per_page' => $comments->perPage(),
                    'total' => $comments->total(),
                    'from' => $comments->firstItem(),
                    'to' => $comments->lastItem(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 创建评论
     */
    public function store(Request $request, Task $task): JsonResponse
    {
        try {
            $data = $request->all();

            // 调试信息
            \Log::info('TaskCommentController store called', [
                'task_id' => $task->id ?? 'null',
                'task_title' => $task->title ?? 'null',
                'data' => $data
            ]);

            // 获取当前用户或Agent
            $user = $request->user();
            $agent = $this->getAgentFromRequest($request);

            $comment = $this->commentService->create($task, $data, $user, $agent);

            return response()->json([
                'success' => true,
                'data' => $comment->load(['user', 'agent']),
                'message' => '评论创建成功',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * 获取单个评论
     */
    public function show(Task $task, TaskComment $comment): JsonResponse
    {
        try {
            // 验证评论属于该任务
            if ($comment->task_id !== $task->id) {
                return response()->json([
                    'success' => false,
                    'error' => '评论不属于该任务',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $comment->load(['user', 'agent', 'replies.user', 'replies.agent']),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 更新评论
     */
    public function update(Request $request, Task $task, TaskComment $comment): JsonResponse
    {
        try {
            // 验证评论属于该任务
            if ($comment->task_id !== $task->id) {
                return response()->json([
                    'success' => false,
                    'error' => '评论不属于该任务',
                ], 404);
            }

            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'error' => '需要用户认证',
                ], 401);
            }

            $data = $request->all();
            $updatedComment = $this->commentService->update($comment, $data, $user);

            return response()->json([
                'success' => true,
                'data' => $updatedComment->load(['user', 'agent']),
                'message' => '评论更新成功',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * 删除评论
     */
    public function destroy(Request $request, Task $task, TaskComment $comment): JsonResponse
    {
        try {
            // 验证评论属于该任务
            if ($comment->task_id !== $task->id) {
                return response()->json([
                    'success' => false,
                    'error' => '评论不属于该任务',
                ], 404);
            }

            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'error' => '需要用户认证',
                ], 401);
            }

            $this->commentService->delete($comment, $user);

            return response()->json([
                'success' => true,
                'message' => '评论删除成功',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * 回复评论
     */
    public function reply(Request $request, Task $task, TaskComment $comment): JsonResponse
    {
        try {
            // 验证评论属于该任务
            if ($comment->task_id !== $task->id) {
                return response()->json([
                    'success' => false,
                    'error' => '评论不属于该任务',
                ], 404);
            }

            $data = $request->all();
            
            // 获取当前用户或Agent
            $user = $request->user();
            $agent = $this->getAgentFromRequest($request);

            $reply = $this->commentService->reply($comment, $data, $user, $agent);

            return response()->json([
                'success' => true,
                'data' => $reply->load(['user', 'agent']),
                'message' => '回复创建成功',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * 获取评论的回复列表
     */
    public function replies(Request $request, Task $task, TaskComment $comment): JsonResponse
    {
        try {
            // 验证评论属于该任务
            if ($comment->task_id !== $task->id) {
                return response()->json([
                    'success' => false,
                    'error' => '评论不属于该任务',
                ], 404);
            }

            $page = $request->get('page', 1);
            $perPage = min($request->get('per_page', 20), 100);

            $replies = $comment->replies()
                ->with(['user', 'agent'])
                ->orderBy('created_at', 'asc')
                ->paginate($perPage, ['*'], 'page', $page);

            return response()->json([
                'success' => true,
                'data' => $replies->items(),
                'pagination' => [
                    'current_page' => $replies->currentPage(),
                    'last_page' => $replies->lastPage(),
                    'per_page' => $replies->perPage(),
                    'total' => $replies->total(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 从请求中获取Agent信息
     */
    protected function getAgentFromRequest(Request $request): ?Agent
    {
        $agentId = $request->header('X-Agent-ID') ?? $request->get('agent_id');
        
        if ($agentId) {
            return Agent::find($agentId);
        }
        
        return null;
    }
}
