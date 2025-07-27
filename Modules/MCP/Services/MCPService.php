<?php

namespace Modules\MCP\Services;

use Psr\Log\LoggerInterface;
use Modules\MCP\Services\AgentService;
use Modules\MCP\Services\AuthorizationService;
use PhpMCP\Laravel\Facades\MCP;

class MCPService
{
    public function __construct(
        private LoggerInterface $logger,
        private AgentService $agentService,
        private AuthorizationService $authorizationService
    ) {
        // MCP服务初始化
    }

    /**
     * 启动MCP服务器
     *
     * @param array $options 服务器启动参数
     * @return bool 服务器启动成功返回true，失败返回false
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
     *
     * @param string $agentId Agent的唯一标识
     * @param string $resource 要访问的资源
     * @param string $action 要执行的操作
     * @return bool 有权限返回true，无权限返回false
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
     *
     * @return array 包含服务器状态信息的数组
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
     *
     * @param string $agentId Agent的唯一标识
     * @param string $action 执行的操作
     * @param array $data 附加的会话数据
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
