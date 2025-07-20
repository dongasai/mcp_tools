<?php

namespace App\Modules\Mcp\Resources;

use App\Modules\Project\Services\ProjectService;
use App\Modules\Mcp\Services\McpService;

class ProjectResource
{
    public function __construct(
        private ProjectService $projectService,
        private McpService $mcpService
    ) {}

    /**
     * 获取资源URI模式
     */
    public function getUriTemplate(): string
    {
        return 'project://{path}';
    }

    /**
     * 获取资源名称
     */
    public function getName(): string
    {
        return 'project';
    }

    /**
     * 获取资源描述
     */
    public function getDescription(): string
    {
        return 'Access to project information and management';
    }

    /**
     * 处理资源请求
     */
    public function read(string $uri): array
    {
        // 解析URI
        $path = $this->parseUri($uri);
        
        // 验证Agent权限
        $agentId = $this->getAgentId();
        if (!$this->mcpService->validateAgentAccess($agentId, 'project', 'read')) {
            throw new \Exception('Access denied');
        }

        // 记录访问
        $this->mcpService->logSession($agentId, 'project_read', ['uri' => $uri]);

        // 根据路径返回不同的数据
        return match ($path) {
            'list' => $this->getProjectList(),
            default => $this->getProject($path)
        };
    }

    /**
     * 获取项目列表
     */
    private function getProjectList(): array
    {
        // 使用模型直接查询，因为 ProjectService 没有 getAllProjects 方法
        $projects = \App\Modules\Project\Models\Project::with(['user'])->get();

        return [
            'type' => 'project_list',
            'data' => $projects->map(function ($project) {
                return [
                    'id' => $project->id,
                    'name' => $project->name,
                    'description' => $project->description,
                    'status' => $project->status,
                    'created_at' => $project->created_at->toISOString(),
                    'updated_at' => $project->updated_at->toISOString(),
                ];
            })->toArray()
        ];
    }

    /**
     * 获取单个项目
     */
    private function getProject(string $projectId): array
    {
        // 使用模型直接查询，因为 ProjectService 没有 findProject 方法
        $project = \App\Modules\Project\Models\Project::with(['user'])->find($projectId);

        if (!$project) {
            throw new \Exception('Project not found');
        }

        return [
            'type' => 'project_detail',
            'data' => [
                'id' => $project->id,
                'name' => $project->name,
                'description' => $project->description,
                'status' => $project->status,
                'timezone' => $project->timezone,
                'repositories' => $project->repositories,
                'settings' => $project->settings,
                'user' => [
                    'id' => $project->user->id,
                    'name' => $project->user->name,
                ],
                'created_at' => $project->created_at->toISOString(),
                'updated_at' => $project->updated_at->toISOString(),
            ]
        ];
    }

    /**
     * 解析URI路径
     */
    private function parseUri(string $uri): string
    {
        // project://list -> list
        // project://123 -> 123
        return str_replace('project://', '', $uri);
    }

    /**
     * 获取当前Agent ID
     */
    private function getAgentId(): string
    {
        // 从MCP会话中获取Agent ID
        // 这里需要根据php-mcp/laravel包的实际实现来获取
        return request()->header('X-Agent-ID', 'unknown');
    }
}
