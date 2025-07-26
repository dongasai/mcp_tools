<?php

namespace Modules\MCP\Tools;

use PhpMCP\Server\Attributes\MCPTool;
use Modules\Dbcont\Services\SqlExecutionService;
use Modules\Dbcont\Services\PermissionService;
use Modules\Dbcont\Models\DatabaseConnection;
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
     *
     * @param int|null $connectionId 数据库连接ID，如果为null则自动选择第一个可用连接
     * @param string $sql SQL查询语句
     * @param int|null $timeout 查询超时时间（秒）
     * @param int|null $maxRows 最大返回行数
     */
    #[MCPTool(name: 'db_execute_sql', description: '执行SQL查询，连接ID可选（默认使用第一个可用连接）')]
    public function executeSql(
        string $sql,
        ?int $connectionId = null,
        ?int $timeout = null,
        ?int $maxRows = null
    ): array
    {
        try {
            // 获取当前Agent
            $agent = $this->getCurrentAgent();

            // 如果未指定连接ID，自动选择第一个可用连接
            $autoSelected = false;
            if ($connectionId === null) {
                $connectionId = $this->getDefaultConnectionId($agent->id);
                if ($connectionId === null) {
                    throw new \Exception('Agent没有可用的数据库连接');
                }
                $autoSelected = true;
            }

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

            // 获取可用连接列表（用于提示信息）
            $availableConnections = $this->permissionService->getAccessibleConnections($agent->id);

            return [
                'success' => true,
                'data' => $result,
                'connection' => [
                    'id' => $connection->id,
                    'name' => $connection->name,
                    'type' => $connection->driver,
                    'auto_selected' => $autoSelected,
                ],
                'execution_info' => [
                    'agent_id' => $agent->id,
                    'executed_at' => now()->toISOString(),
                    'sql_length' => strlen($sql),
                    'available_connections_count' => $availableConnections->count(),
                ],
                'hints' => $autoSelected && $availableConnections->count() > 1 ? [
                    'message' => '自动选择了第一个可用连接，您也可以指定其他连接',
                    'available_connections' => $availableConnections->map(function ($conn) {
                        return [
                            'id' => $conn->id,
                            'name' => $conn->name,
                            'type' => $conn->driver,
                            'status' => $conn->status,
                        ];
                    })->toArray()
                ] : null
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
    #[MCPTool(name: 'db_list_connections', description: '获取数据库连接列表')]
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
     *
     * @param int|null $connectionId 数据库连接ID，如果为null则自动选择第一个可用连接
     */
    #[MCPTool(name: 'db_test_connection', description: '测试数据库连接，连接ID可选（默认使用第一个可用连接）')]
    public function testConnection(?int $connectionId = null): array
    {
        try {
            // 获取当前Agent
            $agent = $this->getCurrentAgent();

            // 如果未指定连接ID，自动选择第一个可用连接
            $autoSelected = false;
            if ($connectionId === null) {
                $connectionId = $this->getDefaultConnectionId($agent->id);
                if ($connectionId === null) {
                    throw new \Exception('Agent没有可用的数据库连接');
                }
                $autoSelected = true;
            }

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

            // 获取可用连接列表（用于提示信息）
            $availableConnections = $this->permissionService->getAccessibleConnections($agent->id);

            return [
                'success' => true,
                'test_result' => $testResult,
                'connection' => [
                    'id' => $connection->id,
                    'name' => $connection->name,
                    'type' => $connection->driver,
                    'status' => $connection->status,
                    'auto_selected' => $autoSelected,
                ],
                'test_info' => [
                    'agent_id' => $agent->id,
                    'tested_at' => now()->toISOString(),
                    'test_time_ms' => $testTime,
                    'available_connections_count' => $availableConnections->count(),
                ],
                'hints' => $autoSelected && $availableConnections->count() > 1 ? [
                    'message' => '自动选择了第一个可用连接，您也可以指定其他连接',
                    'available_connections' => $availableConnections->map(function ($conn) {
                        return [
                            'id' => $conn->id,
                            'name' => $conn->name,
                            'type' => $conn->driver,
                            'status' => $conn->status,
                        ];
                    })->toArray()
                ] : null
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

    /**
     * 获取Agent的默认数据库连接ID
     *
     * @param int $agentId Agent ID
     * @return int|null 连接ID，如果没有可用连接则返回null
     */
    private function getDefaultConnectionId(int $agentId): ?int
    {
        // 获取Agent有权限的所有连接
        $connections = $this->permissionService->getAccessibleConnections($agentId);

        if ($connections->isEmpty()) {
            return null;
        }

        // 按优先级排序：
        // 1. 状态为ACTIVE的连接优先
        // 2. 最近使用的连接优先（通过updated_at判断）
        // 3. 创建时间最新的连接优先
        $sortedConnections = $connections->sortByDesc(function ($connection) {
            $score = 0;

            // 活跃状态加分
            if ($connection->status === 'ACTIVE') {
                $score += 1000;
            }

            // 最近更新时间加分（转换为时间戳）
            $score += $connection->updated_at->timestamp / 1000;

            return $score;
        });

        return $sortedConnections->first()->id;
    }
}
