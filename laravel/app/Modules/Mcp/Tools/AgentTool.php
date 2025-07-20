<?php

namespace App\Modules\Mcp\Tools;

use App\Modules\Agent\Services\AgentService;
use App\Modules\Mcp\Services\McpService;

class AgentTool
{
    public function __construct(
        private AgentService $agentService,
        private McpService $mcpService
    ) {}

    /**
     * 获取工具名称
     */
    public function getName(): string
    {
        return 'agent_manager';
    }

    /**
     * 获取工具描述
     */
    public function getDescription(): string
    {
        return 'Manage agents - create, update, and query agent information';
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
                    'enum' => ['get', 'list', 'update_status', 'get_permissions'],
                    'description' => 'The action to perform'
                ],
                'agent_id' => [
                    'type' => 'string',
                    'description' => 'Agent ID (required for get, update_status, get_permissions actions)'
                ],
                'status' => [
                    'type' => 'string',
                    'enum' => ['active', 'inactive', 'suspended'],
                    'description' => 'Agent status (required for update_status action)'
                ],
                'filters' => [
                    'type' => 'object',
                    'description' => 'Filters for list action',
                    'properties' => [
                        'status' => ['type' => 'string'],
                        'user_id' => ['type' => 'string'],
                        'project_id' => ['type' => 'string']
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
        if (!$this->mcpService->validateAgentAccess($agentId, 'agent', $action)) {
            throw new \Exception('Access denied for action: ' . $action);
        }

        // 记录操作
        $this->mcpService->logSession($agentId, 'agent_tool', [
            'action' => $action,
            'arguments' => $arguments
        ]);

        return match ($action) {
            'get' => $this->getAgent($arguments['agent_id']),
            'list' => $this->listAgents($arguments['filters'] ?? []),
            'update_status' => $this->updateAgentStatus($arguments['agent_id'], $arguments['status']),
            'get_permissions' => $this->getAgentPermissions($arguments['agent_id']),
            default => throw new \Exception('Unknown action: ' . $action)
        };
    }

    /**
     * 获取Agent信息
     */
    private function getAgent(string $agentId): array
    {
        try {
            $agent = $this->agentService->findByAgentId($agentId);

            if (!$agent) {
                return [
                    'success' => false,
                    'message' => 'Agent not found'
                ];
            }

            return [
                'success' => true,
                'data' => [
                    'id' => $agent->id,
                    'agent_id' => $agent->agent_id,
                    'name' => $agent->name,
                    'status' => $agent->status,
                    'user_id' => $agent->user_id,
                    'last_active_at' => $agent->last_active_at?->toISOString(),
                    'created_at' => $agent->created_at->toISOString(),
                    'updated_at' => $agent->updated_at->toISOString()
                ]
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to get agent: ' . $e->getMessage()
            ];
        }
    }

    /**
     * 列出Agents
     */
    private function listAgents(array $filters = []): array
    {
        try {
            // 使用模型直接查询，因为 AgentService 没有 getAgents 方法
            $query = \App\Modules\Agent\Models\Agent::with(['user']);

            // 应用过滤器
            if (isset($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            if (isset($filters['user_id'])) {
                $query->where('user_id', $filters['user_id']);
            }

            $agents = $query->get();

            return [
                'success' => true,
                'data' => $agents->map(function ($agent) {
                    return [
                        'id' => $agent->id,
                        'agent_id' => $agent->agent_id,
                        'name' => $agent->name,
                        'status' => $agent->status,
                        'user_id' => $agent->user_id,
                        'last_active_at' => $agent->last_active_at?->toISOString(),
                        'created_at' => $agent->created_at->toISOString()
                    ];
                })->toArray()
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to list agents: ' . $e->getMessage()
            ];
        }
    }

    /**
     * 更新Agent状态
     */
    private function updateAgentStatus(string $agentId, string $status): array
    {
        try {
            $agent = $this->agentService->findByAgentId($agentId);

            if (!$agent) {
                return [
                    'success' => false,
                    'message' => 'Agent not found'
                ];
            }

            // 使用 AgentService 的 update 方法
            $this->agentService->update($agent, ['status' => $status]);

            return [
                'success' => true,
                'message' => 'Agent status updated successfully',
                'data' => [
                    'agent_id' => $agentId,
                    'status' => $status,
                    'updated_at' => now()->toISOString()
                ]
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to update agent status: ' . $e->getMessage()
            ];
        }
    }

    /**
     * 获取Agent权限
     */
    private function getAgentPermissions(string $agentId): array
    {
        try {
            $agent = $this->agentService->findByAgentId($agentId);

            if (!$agent) {
                return [
                    'success' => false,
                    'message' => 'Agent not found'
                ];
            }

            // 返回Agent的权限信息
            $permissions = [
                'allowed_projects' => $agent->allowed_projects ?? [],
                'allowed_actions' => $agent->allowed_actions ?? [],
                'capabilities' => $agent->capabilities ?? [],
                'configuration' => $agent->configuration ?? []
            ];

            return [
                'success' => true,
                'data' => [
                    'agent_id' => $agentId,
                    'permissions' => $permissions
                ]
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to get agent permissions: ' . $e->getMessage()
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
