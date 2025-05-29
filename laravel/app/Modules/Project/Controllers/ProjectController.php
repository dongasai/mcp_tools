<?php

namespace App\Modules\Project\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Modules\Project\Services\ProjectService;
use App\Modules\Project\Models\Project;
use App\Modules\Core\Contracts\LogInterface;

class ProjectController extends Controller
{
    protected ProjectService $projectService;
    protected LogInterface $logger;

    public function __construct(ProjectService $projectService, LogInterface $logger)
    {
        $this->projectService = $projectService;
        $this->logger = $logger;
    }

    /**
     * 获取项目列表
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

            $filters = $request->only(['status', 'priority', 'agent_id', 'search']);
            $projects = $this->projectService->getUserProjects($user, $filters);

            return response()->json([
                'success' => true,
                'data' => $projects,
                'count' => $projects->count(),
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to get projects', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'filters' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve projects',
            ], 500);
        }
    }

    /**
     * 创建项目
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

            $project = $this->projectService->create($user, $request->all());

            return response()->json([
                'success' => true,
                'data' => $project,
                'message' => 'Project created successfully',
            ], 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            $this->logger->error('Failed to create project', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to create project',
            ], 500);
        }
    }

    /**
     * 获取单个项目
     */
    public function show(Project $project): JsonResponse
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
            if ($project->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthorized',
                ], 403);
            }

            $project->load(['user', 'agent', 'tasks']);
            $stats = $this->projectService->getProjectStats($project);

            return response()->json([
                'success' => true,
                'data' => [
                    'project' => $project,
                    'stats' => $stats,
                ],
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to get project', [
                'user_id' => auth()->id(),
                'project_id' => $project->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve project',
            ], 500);
        }
    }

    /**
     * 更新项目
     */
    public function update(Request $request, Project $project): JsonResponse
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
            if ($project->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthorized',
                ], 403);
            }

            $updatedProject = $this->projectService->update($project, $request->all());

            return response()->json([
                'success' => true,
                'data' => $updatedProject,
                'message' => 'Project updated successfully',
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            $this->logger->error('Failed to update project', [
                'user_id' => auth()->id(),
                'project_id' => $project->id,
                'error' => $e->getMessage(),
                'data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to update project',
            ], 500);
        }
    }

    /**
     * 删除项目
     */
    public function destroy(Project $project): JsonResponse
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
            if ($project->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthorized',
                ], 403);
            }

            $this->projectService->delete($project);

            return response()->json([
                'success' => true,
                'message' => 'Project deleted successfully',
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            $this->logger->error('Failed to delete project', [
                'user_id' => auth()->id(),
                'project_id' => $project->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to delete project',
            ], 500);
        }
    }

    /**
     * 激活项目
     */
    public function activate(Project $project): JsonResponse
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
            if ($project->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthorized',
                ], 403);
            }

            $project->activate();

            return response()->json([
                'success' => true,
                'data' => $project->fresh(),
                'message' => 'Project activated successfully',
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to activate project', [
                'user_id' => auth()->id(),
                'project_id' => $project->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to activate project',
            ], 500);
        }
    }

    /**
     * 完成项目
     */
    public function complete(Project $project): JsonResponse
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
            if ($project->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthorized',
                ], 403);
            }

            $project->complete();

            return response()->json([
                'success' => true,
                'data' => $project->fresh(),
                'message' => 'Project completed successfully',
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to complete project', [
                'user_id' => auth()->id(),
                'project_id' => $project->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to complete project',
            ], 500);
        }
    }
}
