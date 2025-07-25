<?php

namespace App\Modules\Dbcont\Resources;

use PhpMcp\Server\Attributes\McpResource;
use App\Modules\Dbcont\Models\DatabaseConnection;
use App\Modules\Dbcont\Services\PermissionService;
use App\Modules\Agent\Services\AuthenticationService;
use Illuminate\Support\Facades\Log;

class DatabaseConnectionResource
{
    public function __construct(
        private PermissionService $permissionService,
        private AuthenticationService $authService
    ) {}

    /**
     * 获取数据库连接详细信息
     */
    #[McpResource(
        uri: 'db://connection/{id}',
        name: 'db_connection',
        mimeType: 'application/json'
    )]
    public function getDatabaseConnection(string $id): array
    {
        try {
            // 获取当前Agent
            $agent = $this->getCurrentAgent();
            
            // 验证连接ID
            $connectionId = (int) $id;
            if ($connectionId <= 0) {
                throw new \Exception('无效的连接ID');
            }

            // 验证访问权限
            if (!$this->permissionService->hasConnectionAccess($agent->id, $connectionId)) {
                throw new \Exception('Agent无权访问此数据库连接');
            }

            // 获取数据库连接
            $connection = DatabaseConnection::find($connectionId);
            if (!$connection) {
                throw new \Exception('数据库连接不存在');
            }

            // 获取权限信息
            $permission = $this->permissionService->getAgentPermission($agent->id, $connectionId);
            
            // 获取连接统计信息
            $stats = $this->getConnectionStats($connectionId, $agent->id);

            Log::info('Database connection accessed via MCP resource', [
                'agent_id' => $agent->id,
                'connection_id' => $connectionId,
                'permission_level' => $permission?->permission_level
            ]);

            return [
                'type' => 'database_connection',
                'data' => [
                    'connection' => [
                        'id' => $connection->id,
                        'name' => $connection->name,
                        'type' => $connection->driver,
                        'host' => $connection->host,
                        'port' => $connection->port,
                        'database' => $connection->database,
                        'username' => $connection->username,
                        'status' => $connection->status,
                        'description' => $connection->description,
                        'last_tested_at' => $connection->last_tested_at?->toISOString(),
                        'created_at' => $connection->created_at->toISOString(),
                        'updated_at' => $connection->updated_at->toISOString(),
                    ],
                    'permission' => $permission ? [
                        'level' => $permission->permission_level,
                        'allowed_tables' => $permission->allowed_tables,
                        'denied_operations' => $permission->denied_operations,
                        'max_query_time' => $permission->max_query_time,
                        'max_result_rows' => $permission->max_result_rows,
                        'granted_at' => $permission->created_at->toISOString(),
                    ] : null,
                    'statistics' => $stats,
                    'agent_info' => [
                        'id' => $agent->id,
                        'name' => $agent->name,
                        'identifier' => $agent->identifier,
                    ],
                    'access_info' => [
                        'accessed_at' => now()->toISOString(),
                        'access_method' => 'mcp_resource',
                    ]
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Failed to access database connection via MCP resource', [
                'connection_id' => $id,
                'agent_id' => $agent->id ?? null,
                'error' => $e->getMessage()
            ]);

            return [
                'type' => 'error',
                'data' => [
                    'error' => $e->getMessage(),
                    'connection_id' => $id,
                    'timestamp' => now()->toISOString(),
                ]
            ];
        }
    }

    /**
     * 获取所有可访问的数据库连接列表
     */
    #[McpResource(
        uri: 'db://connections',
        name: 'db_connection_list',
        mimeType: 'application/json'
    )]
    public function getDatabaseConnectionList(): array
    {
        try {
            // 获取当前Agent
            $agent = $this->getCurrentAgent();
            
            // 获取Agent有权限的连接
            $connections = $this->permissionService->getAccessibleConnections($agent->id);
            
            $connectionList = $connections->map(function ($connection) use ($agent) {
                $permission = $this->permissionService->getAgentPermission($agent->id, $connection->id);
                $stats = $this->getConnectionStats($connection->id, $agent->id);
                
                return [
                    'id' => $connection->id,
                    'name' => $connection->name,
                    'type' => $connection->driver,
                    'host' => $connection->host,
                    'database' => $connection->database,
                    'status' => $connection->status,
                    'permission_level' => $permission?->permission_level,
                    'last_tested_at' => $connection->last_tested_at?->toISOString(),
                    'statistics' => $stats,
                    'created_at' => $connection->created_at->toISOString(),
                ];
            });

            Log::info('Database connection list accessed via MCP resource', [
                'agent_id' => $agent->id,
                'connection_count' => $connectionList->count()
            ]);

            return [
                'type' => 'database_connection_list',
                'data' => [
                    'connections' => $connectionList->toArray(),
                    'total_count' => $connectionList->count(),
                    'agent_info' => [
                        'id' => $agent->id,
                        'name' => $agent->name,
                        'identifier' => $agent->identifier,
                    ],
                    'access_info' => [
                        'accessed_at' => now()->toISOString(),
                        'access_method' => 'mcp_resource',
                    ]
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Failed to access database connection list via MCP resource', [
                'agent_id' => $agent->id ?? null,
                'error' => $e->getMessage()
            ]);

            return [
                'type' => 'error',
                'data' => [
                    'error' => $e->getMessage(),
                    'timestamp' => now()->toISOString(),
                ]
            ];
        }
    }

    /**
     * 获取连接统计信息
     */
    private function getConnectionStats(int $connectionId, int $agentId): array
    {
        // 获取最近30天的执行统计
        $thirtyDaysAgo = now()->subDays(30);
        
        $totalQueries = \App\Modules\Dbcont\Models\SqlExecutionLog::where('database_connection_id', $connectionId)
            ->where('agent_id', $agentId)
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->count();

        $successfulQueries = \App\Modules\Dbcont\Models\SqlExecutionLog::where('database_connection_id', $connectionId)
            ->where('agent_id', $agentId)
            ->where('success', true)
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->count();

        $avgExecutionTime = \App\Modules\Dbcont\Models\SqlExecutionLog::where('database_connection_id', $connectionId)
            ->where('agent_id', $agentId)
            ->where('success', true)
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->avg('execution_time');

        $lastQuery = \App\Modules\Dbcont\Models\SqlExecutionLog::where('database_connection_id', $connectionId)
            ->where('agent_id', $agentId)
            ->latest()
            ->first();

        return [
            'total_queries_30d' => $totalQueries,
            'successful_queries_30d' => $successfulQueries,
            'success_rate_30d' => $totalQueries > 0 ? round(($successfulQueries / $totalQueries) * 100, 2) : 0,
            'avg_execution_time_30d' => $avgExecutionTime ? round($avgExecutionTime, 3) : null,
            'last_query_at' => $lastQuery?->created_at?->toISOString(),
            'last_query_success' => $lastQuery?->success,
        ];
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
