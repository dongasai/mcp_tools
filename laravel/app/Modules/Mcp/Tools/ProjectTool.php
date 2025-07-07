<?php

namespace App\Modules\Mcp\Tools;

use PhpMcp\Server\Tools\Tool;
use App\Modules\Project\Services\ProjectService;
use App\Modules\Mcp\Services\McpService;

class ProjectTool extends Tool
{
    public function __construct(
        private ProjectService $projectService,
        private McpService $mcpService
    ) {}

    /**
     * 获取工具名称
     */
    public function getName(): string
    {
        return 'project_manager';
    }

    /**
     * 获取工具描述
     */
    public function getDescription(): string
    {
        return 'Manage projects - create, update, and query project information';
    }

    /**
     * 获取工具参数定义
     */
    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'action' => [
                    'type' => 'string',
                    'enum' => ['create', 'update', 'delete', 'get', 'list'],
                    'description' => 'The action to perform'
                ],
                'project_id' => [
                    'type' => 'string',
                    'description' => 'Project ID (required for update, delete, get actions)'
                ],
                'data' => [
                    'type' => 'object',
                    'description' => 'Project data (required for create and update actions)',
                    'properties' => [
                        'name' => ['type' => 'string'],
                        'description' => ['type' => 'string'],
                        'status' => ['type' => 'string', 'enum' => ['active', 'inactive', 'archived']],
                        'timezone' => ['type' => 'string'],
                        'repositories' => ['type' => 'object'],
                        'settings' => ['type' => 'object'],
                    ]
                ]
            ],
            'required' => ['action']
        ];
    }

    /**
     * 执行工具
     */
    public function call(array $arguments): array
    {
        $action = $arguments['action'];
        $agentId = $this->getAgentId();

        // 验证权限
        if (!$this->mcpService->validateAgentAccess($agentId, 'project', $action)) {
            throw new \Exception('Access denied for action: ' . $action);
        }

        // 记录操作
        $this->mcpService->logSession($agentId, 'project_tool', [
            'action' => $action,
            'arguments' => $arguments
        ]);

        return match ($action) {
            'create' => $this->createProject($arguments['data'] ?? []),
            'update' => $this->updateProject($arguments['project_id'], $arguments['data'] ?? []),
            'delete' => $this->deleteProject($arguments['project_id']),
            'get' => $this->getProject($arguments['project_id']),
            'list' => $this->listProjects(),
            default => throw new \Exception('Unknown action: ' . $action)
        };
    }

    /**
     * 创建项目
     */
    private function createProject(array $data): array
    {
        try {
            $project = $this->projectService->createProject($data);
            
            return [
                'success' => true,
                'message' => 'Project created successfully',
                'data' => [
                    'id' => $project->id,
                    'name' => $project->name,
                    'status' => $project->status,
                    'created_at' => $project->created_at->toISOString()
                ]
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to create project: ' . $e->getMessage()
            ];
        }
    }

    /**
     * 更新项目
     */
    private function updateProject(string $projectId, array $data): array
    {
        try {
            $project = $this->projectService->updateProject($projectId, $data);
            
            return [
                'success' => true,
                'message' => 'Project updated successfully',
                'data' => [
                    'id' => $project->id,
                    'name' => $project->name,
                    'status' => $project->status,
                    'updated_at' => $project->updated_at->toISOString()
                ]
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to update project: ' . $e->getMessage()
            ];
        }
    }

    /**
     * 删除项目
     */
    private function deleteProject(string $projectId): array
    {
        try {
            $this->projectService->deleteProject($projectId);
            
            return [
                'success' => true,
                'message' => 'Project deleted successfully'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to delete project: ' . $e->getMessage()
            ];
        }
    }

    /**
     * 获取项目
     */
    private function getProject(string $projectId): array
    {
        try {
            $project = $this->projectService->findProject($projectId);
            
            if (!$project) {
                return [
                    'success' => false,
                    'message' => 'Project not found'
                ];
            }

            return [
                'success' => true,
                'data' => [
                    'id' => $project->id,
                    'name' => $project->name,
                    'description' => $project->description,
                    'status' => $project->status,
                    'timezone' => $project->timezone,
                    'repositories' => $project->repositories,
                    'settings' => $project->settings,
                    'created_at' => $project->created_at->toISOString(),
                    'updated_at' => $project->updated_at->toISOString()
                ]
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to get project: ' . $e->getMessage()
            ];
        }
    }

    /**
     * 列出项目
     */
    private function listProjects(): array
    {
        try {
            $projects = $this->projectService->getAllProjects();
            
            return [
                'success' => true,
                'data' => $projects->map(function ($project) {
                    return [
                        'id' => $project->id,
                        'name' => $project->name,
                        'status' => $project->status,
                        'created_at' => $project->created_at->toISOString()
                    ];
                })->toArray()
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to list projects: ' . $e->getMessage()
            ];
        }
    }

    /**
     * 获取当前Agent ID
     */
    private function getAgentId(): string
    {
        return request()->header('X-Agent-ID', 'unknown');
    }
}
