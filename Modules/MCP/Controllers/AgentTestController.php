<?php

namespace Modules\MCP\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Agent\Models\Agent;
use Modules\Agent\Services\AgentService;
use Modules\User\Models\User;

class AgentTestController extends Controller
{
    protected AgentService $agentService;

    public function __construct(AgentService $agentService)
    {
        $this->agentService = $agentService;
    }

    /**
     * 快速创建Agent测试
     */
    public function quickCreate(Request $request): JsonResponse
    {
        try {
            $data = $request->all();
            
            // 简单验证
            if (empty($data['name']) || empty($data['user_id'])) {
                return response()->json([
                    'success' => false,
                    'error' => 'Name and user_id are required',
                ], 400);
            }

            // 查找用户
            $user = User::find($data['user_id']);
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'error' => 'User not found',
                ], 404);
            }

            // 创建Agent
            $agent = $this->agentService->create($user, [
                'name' => $data['name'],
                'description' => $data['description'] ?? 'Test agent created via API',
                'capabilities' => $data['capabilities'] ?? ['code_generation', 'testing'],
                'configuration' => $data['configuration'] ?? [],
            ]);

            return response()->json([
                'success' => true,
                'data' => $agent,
                'message' => 'Agent created successfully',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to create agent: ' . $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    }

    /**
     * 获取所有Agent
     */
    public function getAgents(): JsonResponse
    {
        try {
            $agents = Agent::with(['user'])->get();
            
            return response()->json([
                'success' => true,
                'data' => $agents,
                'count' => $agents->count(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to get agents: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 获取Agent统计信息
     */
    public function getStats(): JsonResponse
    {
        try {
            $stats = $this->agentService->getSystemStats();
            
            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to get stats: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 根据Agent ID查找Agent
     */
    public function findByAgentId(string $agentId): JsonResponse
    {
        try {
            $agent = $this->agentService->findByAgentId($agentId);
            
            if (!$agent) {
                return response()->json([
                    'success' => false,
                    'error' => 'Agent not found',
                ], 404);
            }

            $agent->load(['user']);
            
            return response()->json([
                'success' => true,
                'data' => $agent,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to find agent: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 测试Agent激活
     */
    public function testActivate(int $id): JsonResponse
    {
        try {
            $agent = Agent::find($id);
            
            if (!$agent) {
                return response()->json([
                    'success' => false,
                    'error' => 'Agent not found',
                ], 404);
            }

            $activatedAgent = $this->agentService->activate($agent);
            
            return response()->json([
                'success' => true,
                'data' => $activatedAgent,
                'message' => 'Agent activated successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to activate agent: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 测试Agent停用
     */
    public function testDeactivate(int $id): JsonResponse
    {
        try {
            $agent = Agent::find($id);
            
            if (!$agent) {
                return response()->json([
                    'success' => false,
                    'error' => 'Agent not found',
                ], 404);
            }

            $deactivatedAgent = $this->agentService->deactivate($agent);
            
            return response()->json([
                'success' => true,
                'data' => $deactivatedAgent,
                'message' => 'Agent deactivated successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to deactivate agent: ' . $e->getMessage(),
            ], 500);
        }
    }
}
