<?php

namespace Modules\MCP\Services;

use App\Modules\Core\Contracts\LogInterface;
use Modules\MCP\Services\AgentService;
use Modules\MCP\Services\AuthorizationService;
use PhpMCP\Laravel\Facades\MCP;

class MCPService
{
    public function __construct(
        private LogInterface $logger,
        private AgentService $agentService,
        private AuthorizationService $authorizationService
    ) {}

    /**
     * 启动MCP服务器
     */
    public function startServer(array $options = []): bool
    {
        try {
            $this->logger->info('Starting MCP server', $options);
            
            // 使用php-mcp/laravel包启动服务器
            return MCP::serve($options);
        } catch (\Exception $e) {
            $this->logger->error('Failed to start MCP server', [
                'error' => $e->getMessage(),
                'options' => $options
            ]);
            
            return false;
        }
    }

    /**
     * 验证Agent权限
     */
    public function validateAgentAccess(string $agentId, string $resource, string $action): bool
    {
        try {
            // 通过Agent服务验证权限
            $agent = $this->agentService->findByAgentId($agentId);
            
            if (!$agent) {
                $this->logger->warning('Agent not found', ['agent_id' => $agentId]);
                return false;
            }

            if ($agent->status !== 'active') {
                $this->logger->warning('Agent is not active', ['agent_id' => $agentId, 'status' => $agent->status]);
                return false;
            }

            // 检查Agent权限
            return $this->authorizationService->canPerformAction($agent, $action);
        } catch (\Exception $e) {
            $this->logger->error('Failed to validate agent access', [
                'agent_id' => $agentId,
                'resource' => $resource,
                'action' => $action,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * 获取MCP服务器状态
     */
    public function getServerStatus(): array
    {
        return [
            'status' => 'running',
            'version' => config('mcp.server.version', '1.0.0'),
            'transport' => config('mcp.server.transport', 'http'),
            'capabilities' => config('mcp.capabilities', []),
            'resources_count' => count(config('mcp.resources', [])),
            'tools_count' => count(config('mcp.tools', [])),
        ];
    }

    /**
     * 记录MCP会话
     */
    public function logSession(string $agentId, string $action, array $data = []): void
    {
        $this->logger->info('MCP session activity', [
            'agent_id' => $agentId,
            'action' => $action,
            'data' => $data,
            'timestamp' => now()->toISOString()
        ]);
    }
}
