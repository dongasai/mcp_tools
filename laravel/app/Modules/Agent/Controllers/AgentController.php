<?php

namespace App\Modules\Agent\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Modules\Agent\Services\AgentService;
use App\Modules\Agent\Models\Agent;
use App\Modules\Core\Contracts\LogInterface;

class AgentController extends Controller
{
    protected AgentService $agentService;
    protected LogInterface $logger;

    public function __construct(AgentService $agentService, LogInterface $logger)
    {
        $this->agentService = $agentService;
        $this->logger = $logger;
    }

    /**
     * 获取Agent列表
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthenticated',
                ], 401);
            }

            $filters = $request->only(['status', 'capability', 'search']);
            $agents = $this->agentService->getUserAgents($user, $filters);

            return response()->json([
                'success' => true,
                'data' => $agents,
                'count' => $agents->count(),
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to get agents', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'filters' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve agents',
            ], 500);
        }
    }

    /**
     * 创建Agent
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthenticated',
                ], 401);
            }

            $agent = $this->agentService->create($user, $request->all());

            return response()->json([
                'success' => true,
                'data' => $agent,
                'message' => 'Agent created successfully',
            ], 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            $this->logger->error('Failed to create agent', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to create agent',
            ], 500);
        }
    }

    /**
     * 获取单个Agent
     */
    public function show(Agent $agent): JsonResponse
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthenticated',
                ], 401);
            }

            // 检查权限
            if ($agent->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthorized',
                ], 403);
            }

            $agent->load(['user', 'tasks', 'projects']);
            $stats = $this->agentService->getAgentStats($agent);

            return response()->json([
                'success' => true,
                'data' => [
                    'agent' => $agent,
                    'stats' => $stats,
                ],
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to get agent', [
                'user_id' => auth()->id(),
                'agent_id' => $agent->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve agent',
            ], 500);
        }
    }

    /**
     * 更新Agent
     */
    public function update(Request $request, Agent $agent): JsonResponse
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthenticated',
                ], 401);
            }

            // 检查权限
            if ($agent->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthorized',
                ], 403);
            }

            $updatedAgent = $this->agentService->update($agent, $request->all());

            return response()->json([
                'success' => true,
                'data' => $updatedAgent,
                'message' => 'Agent updated successfully',
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            $this->logger->error('Failed to update agent', [
                'user_id' => auth()->id(),
                'agent_id' => $agent->id,
                'error' => $e->getMessage(),
                'data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to update agent',
            ], 500);
        }
    }

    /**
     * 删除Agent
     */
    public function destroy(Agent $agent): JsonResponse
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthenticated',
                ], 401);
            }

            // 检查权限
            if ($agent->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthorized',
                ], 403);
            }

            $this->agentService->delete($agent);

            return response()->json([
                'success' => true,
                'message' => 'Agent deleted successfully',
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            $this->logger->error('Failed to delete agent', [
                'user_id' => auth()->id(),
                'agent_id' => $agent->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to delete agent',
            ], 500);
        }
    }

    /**
     * 激活Agent
     */
    public function activate(Agent $agent): JsonResponse
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthenticated',
                ], 401);
            }

            // 检查权限
            if ($agent->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthorized',
                ], 403);
            }

            $activatedAgent = $this->agentService->activate($agent);

            return response()->json([
                'success' => true,
                'data' => $activatedAgent,
                'message' => 'Agent activated successfully',
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to activate agent', [
                'user_id' => auth()->id(),
                'agent_id' => $agent->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to activate agent',
            ], 500);
        }
    }

    /**
     * 停用Agent
     */
    public function deactivate(Agent $agent): JsonResponse
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthenticated',
                ], 401);
            }

            // 检查权限
            if ($agent->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthorized',
                ], 403);
            }

            $deactivatedAgent = $this->agentService->deactivate($agent);

            return response()->json([
                'success' => true,
                'data' => $deactivatedAgent,
                'message' => 'Agent deactivated successfully',
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to deactivate agent', [
                'user_id' => auth()->id(),
                'agent_id' => $agent->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to deactivate agent',
            ], 500);
        }
    }
}
