<?php

namespace Modules\MCP\Resources;

use PhpMCP\Server\Attributes\{MCPResource, MCPResourceTemplate};
use Modules\Dbcont\Models\SqlExecutionLog;
use App\Modules\Agent\Services\AuthenticationService;
use Illuminate\Support\Facades\Log;

class SqlExecutionLogResource
{
    public function __construct(
        private AuthenticationService $authService
    ) {}

    /**
     * 获取Agent的SQL执行日志
     */
    #[MCPResourceTemplate(
        uriTemplate: 'db://log/{agentId}',
        name: 'db_execution_log',
        description: '获取Agent的SQL执行日志',
        mimeType: 'application/json'
    )]
    public function getSqlExecutionLog(string $agentId, array $params = []): array
    {
        try {
            // 获取当前Agent
            $currentAgent = $this->getCurrentAgent();
            
            // 验证权限：只能查看自己的日志
            if ($currentAgent->id != (int)$agentId) {
                throw new \Exception('只能查看自己的SQL执行日志');
            }

            // 解析查询参数
            $limit = min((int)($params['limit'] ?? 50), 1000); // 最大1000条
            $offset = max((int)($params['offset'] ?? 0), 0);
            $connectionId = isset($params['connection_id']) ? (int)$params['connection_id'] : null;
            $success = isset($params['success']) ? filter_var($params['success'], FILTER_VALIDATE_BOOLEAN) : null;
            $startDate = $params['start_date'] ?? null;
            $endDate = $params['end_date'] ?? null;

            // 构建查询
            $query = SqlExecutionLog::where('agent_id', $agentId)
                ->with(['databaseConnection'])
                ->orderBy('created_at', 'desc');

            // 应用筛选条件
            if ($connectionId) {
                $query->where('database_connection_id', $connectionId);
            }

            if ($success !== null) {
                $query->where('success', $success);
            }

            if ($startDate) {
                $query->where('created_at', '>=', $startDate);
            }

            if ($endDate) {
                $query->where('created_at', '<=', $endDate);
            }

            // 获取总数
            $totalCount = $query->count();

            // 获取分页数据
            $logs = $query->offset($offset)->limit($limit)->get();

            // 格式化日志数据
            $logData = $logs->map(function ($log) {
                return [
                    'id' => $log->id,
                    'sql_statement' => $log->sql_statement,
                    'success' => $log->success,
                    'error_message' => $log->error_message,
                    'execution_time' => $log->execution_time,
                    'affected_rows' => $log->affected_rows,
                    'executed_at' => $log->created_at->toISOString(),
                    'connection' => [
                        'id' => $log->databaseConnection->id,
                        'name' => $log->databaseConnection->name,
                        'type' => $log->databaseConnection->driver,
                        'database' => $log->databaseConnection->database,
                    ],
                ];
            });

            // 获取统计信息
            $stats = $this->getExecutionStats($agentId, $params);

            Log::info('SQL execution log accessed via MCP resource', [
                'agent_id' => $agentId,
                'log_count' => $logs->count(),
                'total_count' => $totalCount,
                'filters' => array_filter($params)
            ]);

            return [
                'type' => 'sql_execution_log',
                'data' => [
                    'logs' => $logData->toArray(),
                    'pagination' => [
                        'total_count' => $totalCount,
                        'limit' => $limit,
                        'offset' => $offset,
                        'has_more' => ($offset + $limit) < $totalCount,
                    ],
                    'statistics' => $stats,
                    'agent_info' => [
                        'id' => $currentAgent->id,
                        'name' => $currentAgent->name,
                        'identifier' => $currentAgent->identifier,
                    ],
                    'access_info' => [
                        'accessed_at' => now()->toISOString(),
                        'access_method' => 'mcp_resource',
                        'filters_applied' => array_filter($params),
                    ]
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Failed to access SQL execution log via MCP resource', [
                'agent_id' => $agentId,
                'current_agent_id' => $currentAgent->id ?? null,
                'error' => $e->getMessage()
            ]);

            return [
                'type' => 'error',
                'data' => [
                    'error' => $e->getMessage(),
                    'agent_id' => $agentId,
                    'timestamp' => now()->toISOString(),
                ]
            ];
        }
    }

    /**
     * 获取SQL执行统计信息
     */
    #[MCPResourceTemplate(
        uriTemplate: 'db://stats/{agentId}',
        name: 'db_execution_stats',
        description: '获取SQL执行统计信息',
        mimeType: 'application/json'
    )]
    public function getSqlExecutionStats(string $agentId, array $params = []): array
    {
        try {
            // 获取当前Agent
            $currentAgent = $this->getCurrentAgent();
            
            // 验证权限：只能查看自己的统计
            if ($currentAgent->id != (int)$agentId) {
                throw new \Exception('只能查看自己的SQL执行统计');
            }

            $stats = $this->getExecutionStats($agentId, $params);

            Log::info('SQL execution stats accessed via MCP resource', [
                'agent_id' => $agentId,
                'stats_period' => $params['period'] ?? 'all_time'
            ]);

            return [
                'type' => 'sql_execution_stats',
                'data' => [
                    'statistics' => $stats,
                    'agent_info' => [
                        'id' => $currentAgent->id,
                        'name' => $currentAgent->name,
                        'identifier' => $currentAgent->identifier,
                    ],
                    'access_info' => [
                        'accessed_at' => now()->toISOString(),
                        'access_method' => 'mcp_resource',
                        'period' => $params['period'] ?? 'all_time',
                    ]
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Failed to access SQL execution stats via MCP resource', [
                'agent_id' => $agentId,
                'current_agent_id' => $currentAgent->id ?? null,
                'error' => $e->getMessage()
            ]);

            return [
                'type' => 'error',
                'data' => [
                    'error' => $e->getMessage(),
                    'agent_id' => $agentId,
                    'timestamp' => now()->toISOString(),
                ]
            ];
        }
    }

    /**
     * 获取执行统计信息
     */
    private function getExecutionStats(string $agentId, array $params = []): array
    {
        $period = $params['period'] ?? '30d';
        
        // 确定时间范围
        $startDate = match($period) {
            '1d' => now()->subDay(),
            '7d' => now()->subDays(7),
            '30d' => now()->subDays(30),
            '90d' => now()->subDays(90),
            default => null
        };

        $query = SqlExecutionLog::where('agent_id', $agentId);
        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        $totalQueries = $query->count();
        $successfulQueries = $query->where('success', true)->count();
        $failedQueries = $totalQueries - $successfulQueries;
        
        $avgExecutionTime = SqlExecutionLog::where('agent_id', $agentId)
            ->where('success', true)
            ->when($startDate, fn($q) => $q->where('created_at', '>=', $startDate))
            ->avg('execution_time');

        $slowestQuery = SqlExecutionLog::where('agent_id', $agentId)
            ->where('success', true)
            ->when($startDate, fn($q) => $q->where('created_at', '>=', $startDate))
            ->orderBy('execution_time', 'desc')
            ->first();

        // 按连接统计
        $connectionStats = SqlExecutionLog::where('agent_id', $agentId)
            ->when($startDate, fn($q) => $q->where('created_at', '>=', $startDate))
            ->selectRaw('database_connection_id, COUNT(*) as query_count, AVG(execution_time) as avg_time')
            ->groupBy('database_connection_id')
            ->with('databaseConnection:id,name,driver')
            ->get()
            ->map(function ($stat) {
                return [
                    'connection_id' => $stat->database_connection_id,
                    'connection_name' => $stat->databaseConnection->name ?? 'Unknown',
                    'connection_type' => $stat->databaseConnection->driver ?? 'Unknown',
                    'query_count' => $stat->query_count,
                    'avg_execution_time' => round($stat->avg_time, 3),
                ];
            });

        return [
            'period' => $period,
            'total_queries' => $totalQueries,
            'successful_queries' => $successfulQueries,
            'failed_queries' => $failedQueries,
            'success_rate' => $totalQueries > 0 ? round(($successfulQueries / $totalQueries) * 100, 2) : 0,
            'avg_execution_time' => $avgExecutionTime ? round($avgExecutionTime, 3) : null,
            'slowest_query' => $slowestQuery ? [
                'execution_time' => $slowestQuery->execution_time,
                'sql_preview' => substr($slowestQuery->sql_statement, 0, 100),
                'executed_at' => $slowestQuery->created_at->toISOString(),
            ] : null,
            'by_connection' => $connectionStats->toArray(),
            'generated_at' => now()->toISOString(),
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
