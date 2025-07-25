<?php

namespace App\Modules\Dbcont\Tools;

use PhpMcp\Server\Attributes\McpTool;
use App\Modules\Dbcont\Services\SqlExecutionService;
use App\Modules\Dbcont\Services\PermissionService;
use App\Modules\Dbcont\Models\DatabaseConnection;
use App\Modules\Agent\Services\AuthenticationService;
use Illuminate\Support\Facades\Log;

class SqlExecutionTool
{
    public function __construct(
        private SqlExecutionService $sqlService,
        private PermissionService $permissionService,
        private AuthenticationService $authService
    ) {}

    /**
     * 执行SQL查询
     */
    #[McpTool(name: 'execute_sql', description: '执行SQL查询')]
    public function executeSql(
        int $connectionId,
        string $sql,
        ?int $timeout = null,
        ?int $maxRows = null
    ): array
    {
        try {
            // 获取当前Agent
            $agent = $this->getCurrentAgent();
            
            // 验证连接访问权限
            if (!$this->permissionService->hasConnectionAccess($agent->id, $connectionId)) {
                throw new \Exception('Agent无权访问此数据库连接');
            }

            // 获取数据库连接
            $connection = DatabaseConnection::find($connectionId);
            if (!$connection) {
                throw new \Exception('数据库连接不存在');
            }

            // 检查连接状态
            if ($connection->status !== 'active') {
                throw new \Exception('数据库连接未激活');
            }

            // 准备执行选项
            $options = [];
            if ($timeout) {
                $options['timeout'] = min($timeout, 300); // 最大5分钟
            }
            if ($maxRows) {
                $options['max_rows'] = min($maxRows, 10000); // 最大1万行
            }

            // 执行SQL
            $result = $this->sqlService->executeQuery(
                $connectionId,
                $sql,
                $agent->id,
                $options
            );

            Log::info('SQL executed via MCP', [
                'agent_id' => $agent->id,
                'connection_id' => $connectionId,
                'sql_preview' => substr($sql, 0, 100),
                'success' => $result['success'],
                'execution_time' => $result['execution_time'] ?? null
            ]);

            return [
                'success' => true,
                'data' => $result,
                'connection' => [
                    'id' => $connection->id,
                    'name' => $connection->name,
                    'type' => $connection->driver,
                ],
                'execution_info' => [
                    'agent_id' => $agent->id,
                    'executed_at' => now()->toISOString(),
                    'sql_length' => strlen($sql),
                ]
            ];

        } catch (\Exception $e) {
            Log::error('SQL execution failed via MCP', [
                'connection_id' => $connectionId ?? null,
                'agent_id' => $agent->id ?? null,
                'sql_preview' => isset($sql) ? substr($sql, 0, 50) : null,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'context' => [
                    'tool' => 'execute_sql',
                    'connection_id' => $connectionId ?? null,
                    'agent_id' => $agent->id ?? null,
                ]
            ];
        }
    }

    /**
     * 获取数据库连接列表
     */
    #[McpTool(name: 'list_connections', description: '获取数据库连接列表')]
    public function listConnections(): array
    {
        try {
            // 获取当前Agent
            $agent = $this->getCurrentAgent();
            
            // 获取Agent有权限的连接
            $connections = $this->permissionService->getAccessibleConnections($agent->id);
            
            $connectionList = $connections->map(function ($connection) use ($agent) {
                $permission = $this->permissionService->getPermissionLevel($agent->id, $connection->id);
                
                return [
                    'id' => $connection->id,
                    'name' => $connection->name,
                    'type' => $connection->driver,
                    'host' => $connection->host,
                    'database' => $connection->database,
                    'status' => $connection->status,
                    'permission_level' => $permission->value,
                    'last_tested_at' => $connection->last_tested_at?->toISOString(),
                    'created_at' => $connection->created_at->toISOString(),
                ];
            });

            Log::info('Database connections listed via MCP', [
                'agent_id' => $agent->id,
                'connection_count' => $connectionList->count()
            ]);

            return [
                'success' => true,
                'connections' => $connectionList->toArray(),
                'total_count' => $connectionList->count(),
                'agent_info' => [
                    'id' => $agent->id,
                    'name' => $agent->name,
                    'identifier' => $agent->identifier,
                ]
            ];

        } catch (\Exception $e) {
            Log::error('List connections failed via MCP', [
                'agent_id' => $agent->id ?? null,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'context' => [
                    'tool' => 'list_connections',
                    'agent_id' => $agent->id ?? null,
                ]
            ];
        }
    }

    /**
     * 测试数据库连接
     */
    #[McpTool(name: 'test_connection', description: '测试数据库连接')]
    public function testConnection(int $connectionId): array
    {
        try {
            // 获取当前Agent
            $agent = $this->getCurrentAgent();
            
            // 验证连接访问权限
            if (!$this->permissionService->hasConnectionAccess($agent->id, $connectionId)) {
                throw new \Exception('Agent无权访问此数据库连接');
            }

            // 获取数据库连接
            $connection = DatabaseConnection::find($connectionId);
            if (!$connection) {
                throw new \Exception('数据库连接不存在');
            }

            // 执行连接测试
            $startTime = microtime(true);
            $testResult = $this->sqlService->testConnection($connection);
            $testTime = round((microtime(true) - $startTime) * 1000, 2); // 毫秒

            // 更新连接的最后测试时间
            if ($testResult['success']) {
                $connection->update([
                    'last_tested_at' => now(),
                    'status' => 'ACTIVE'
                ]);
            } else {
                $connection->update(['status' => 'ERROR']);
            }

            Log::info('Database connection tested via MCP', [
                'agent_id' => $agent->id,
                'connection_id' => $connectionId,
                'success' => $testResult['success'],
                'test_time_ms' => $testTime
            ]);

            return [
                'success' => true,
                'test_result' => $testResult,
                'connection' => [
                    'id' => $connection->id,
                    'name' => $connection->name,
                    'type' => $connection->driver,
                    'status' => $connection->status,
                ],
                'test_info' => [
                    'agent_id' => $agent->id,
                    'tested_at' => now()->toISOString(),
                    'test_time_ms' => $testTime,
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Test connection failed via MCP', [
                'connection_id' => $connectionId ?? null,
                'agent_id' => $agent->id ?? null,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'context' => [
                    'tool' => 'test_connection',
                    'connection_id' => $connectionId ?? null,
                    'agent_id' => $agent->id ?? null,
                ]
            ];
        }
    }

    /**
     * 获取当前Agent
     */
    private function getCurrentAgent()
    {
        $agentId = $this->getCurrentAgentId();
        if (!$agentId) {
            throw new \Exception('无法获取Agent身份信息');
        }

        $agent = $this->authService->findByAgentId($agentId);
        if (!$agent) {
            throw new \Exception('Agent不存在');
        }

        return $agent;
    }

    /**
     * 获取当前Agent ID
     */
    private function getCurrentAgentId(): ?string
    {
        // 从MCP中间件设置的请求属性中获取Agent ID
        return request()->attributes->get('mcp_agent_id') ??
               request()->header('X-MCP-Agent-ID') ??
               session('mcp_agent_id') ??
               config('mcp.default_agent_id');
    }
}
