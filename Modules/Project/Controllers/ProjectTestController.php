<?php

namespace Modules\Project\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Project\Models\Project;
use Modules\Project\Services\ProjectService;
use App\Modules\User\Models\User;
use App\Modules\Agent\Models\Agent;

class ProjectTestController extends Controller
{
    protected ProjectService $projectService;

    public function __construct(ProjectService $projectService)
    {
        $this->projectService = $projectService;
    }

    /**
     * 快速创建项目测试
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

            // 创建项目
            $project = $this->projectService->create($user, [
                'name' => $data['name'],
                'description' => $data['description'] ?? 'Test project created via API',
                'repository_url' => $data['repository_url'] ?? null,
                'branch' => $data['branch'] ?? 'main',
                'agent_id' => $data['agent_id'] ?? null,
                'priority' => $data['priority'] ?? 'medium',
                'settings' => $data['settings'] ?? [],
            ]);

            return response()->json([
                'success' => true,
                'data' => $project,
                'message' => 'Project created successfully',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to create project: ' . $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    }

    /**
     * 获取所有项目
     */
    public function getProjects(): JsonResponse
    {
        try {
            $projects = Project::with(['user', 'agent'])->get();
            
            return response()->json([
                'success' => true,
                'data' => $projects,
                'count' => $projects->count(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to get projects: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 获取项目统计信息
     */
    public function getStats(): JsonResponse
    {
        try {
            $stats = $this->projectService->getSystemStats();
            
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
     * 获取用户的项目
     */
    public function getUserProjects(int $userId): JsonResponse
    {
        try {
            $user = User::find($userId);
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'error' => 'User not found',
                ], 404);
            }

            $projects = $this->projectService->getUserProjects($user);
            
            return response()->json([
                'success' => true,
                'data' => $projects,
                'count' => $projects->count(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to get user projects: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 获取Agent的项目
     */
    public function getAgentProjects(int $agentId): JsonResponse
    {
        try {
            $agent = Agent::find($agentId);
            if (!$agent) {
                return response()->json([
                    'success' => false,
                    'error' => 'Agent not found',
                ], 404);
            }

            $projects = $this->projectService->getAgentProjects($agent);
            
            return response()->json([
                'success' => true,
                'data' => $projects,
                'count' => $projects->count(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to get agent projects: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 测试项目激活
     */
    public function testActivate(int $id): JsonResponse
    {
        try {
            $project = Project::find($id);
            
            if (!$project) {
                return response()->json([
                    'success' => false,
                    'error' => 'Project not found',
                ], 404);
            }

            $project->activate();
            
            return response()->json([
                'success' => true,
                'data' => $project->fresh(),
                'message' => 'Project activated successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to activate project: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 测试项目完成
     */
    public function testComplete(int $id): JsonResponse
    {
        try {
            $project = Project::find($id);
            
            if (!$project) {
                return response()->json([
                    'success' => false,
                    'error' => 'Project not found',
                ], 404);
            }

            $project->complete();
            
            return response()->json([
                'success' => true,
                'data' => $project->fresh(),
                'message' => 'Project completed successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to complete project: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 获取项目详细统计
     */
    public function getProjectStats(int $id): JsonResponse
    {
        try {
            $project = Project::find($id);
            
            if (!$project) {
                return response()->json([
                    'success' => false,
                    'error' => 'Project not found',
                ], 404);
            }

            $stats = $this->projectService->getProjectStats($project);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'project' => $project,
                    'stats' => $stats,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to get project stats: ' . $e->getMessage(),
            ], 500);
        }
    }
}
