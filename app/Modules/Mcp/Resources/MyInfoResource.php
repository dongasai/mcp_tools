<?php

namespace App\Modules\Mcp\Resources;

use PhpMcp\Server\Attributes\McpResource;
use App\Modules\Agent\Services\AuthenticationService;
use App\Modules\Project\Models\Project;

class MyInfoResource
{
    public function __construct(
        private AuthenticationService $authService
    ) {}

    /**
     * 获取我的完整信息（Agent + 项目）
     */
    #[McpResource(
        uri: 'myinfo://get',
        name: 'myInfo',
        mimeType: 'application/json'
    )]
    public function getMyInfo(): array
    {
        $agent = $this->getCurrentAgent();
        $user = $agent->user;
        
        // 获取用户的项目
        $projects = Project::where('user_id', $user->id)->get();
        
        // 获取Agent允许访问的项目
        $allowedProjectIds = $agent->allowed_projects ?? [];
        $allowedProjects = $projects->whereIn('id', $allowedProjectIds);

        return [
            'type' => 'my_info',
            'data' => [
                'agent' => [
                    'id' => $agent->id,
                    'identifier' => $agent->identifier,
                    'name' => $agent->name,
                    'status' => $agent->status,
                    'description' => $agent->description,
                    'capabilities' => $agent->capabilities,
                    'configuration' => $agent->configuration,
                    'allowed_actions' => $agent->allowed_actions,
                    'last_active_at' => $agent->last_active_at?->toISOString(),
                    'created_at' => $agent->created_at->toISOString(),
                ],
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'username' => $user->username,
                    'email' => $user->email,
                ],
                'projects' => [
                    'total' => $projects->count(),
                    'allowed' => $allowedProjects->count(),
                    'list' => $allowedProjects->map(function ($project) {
                        return [
                            'id' => $project->id,
                            'name' => $project->name,
                            'description' => $project->description,
                            'status' => $project->status,
                            'repository_url' => $project->repository_url,
                            'settings' => $project->settings,
                            'created_at' => $project->created_at->toISOString(),
                            'updated_at' => $project->updated_at->toISOString(),
                        ];
                    })->values()->toArray(),
                ],
                'permissions' => [
                    'allowed_projects' => $allowedProjectIds,
                    'allowed_actions' => $agent->allowed_actions,
                ],
                'statistics' => [
                    'total_projects' => $projects->count(),
                    'accessible_projects' => $allowedProjects->count(),
                    'active_projects' => $projects->where('status', 'active')->count(),
                ],
            ]
        ];
    }



    /**
     * 获取当前认证的Agent
     */
    private function getCurrentAgent(): \App\Modules\Agent\Models\Agent
    {
        $authInfo = $this->authService->extractAuthFromRequest(request());

        if (!$authInfo['token']) {
            throw new \Exception('No authentication token provided');
        }

        $agent = $this->authService->authenticate($authInfo['token'], $authInfo['agent_id']);

        if (!$agent) {
            throw new \Exception('Invalid authentication token or agent ID');
        }

        return $agent;
    }
}
