<?php

namespace App\Modules\Mcp\Services;

use App\Modules\Mcp\Models\Agent;
use App\Modules\Core\Services\LogService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class SessionService
{
    public function __construct(
        private LogService $logger
    ) {}

    /**
     * 创建MCP会话
     */
    public function createSession(Agent $agent, array $context = []): string
    {
        $sessionId = 'mcp_session_' . Str::random(32);
        
        $sessionData = [
            'session_id' => $sessionId,
            'agent_id' => $agent->identifier,
            'agent_db_id' => $agent->id,
            'user_id' => $agent->user_id,
            'created_at' => now()->toISOString(),
            'last_activity' => now()->toISOString(),
            'context' => $context,
            'request_count' => 0,
            'tool_calls' => [],
            'resource_accesses' => [],
            'errors' => []
        ];

        // 缓存会话数据（默认1小时）
        Cache::put("mcp_session:{$sessionId}", $sessionData, 3600);

        $this->logger->info('MCP session created', [
            'session_id' => $sessionId,
            'agent_id' => $agent->identifier,
            'user_id' => $agent->user_id,
            'context' => $context
        ]);

        return $sessionId;
    }

    /**
     * 获取会话数据
     */
    public function getSession(string $sessionId): ?array
    {
        return Cache::get("mcp_session:{$sessionId}");
    }

    /**
     * 更新会话活动时间
     */
    public function updateActivity(string $sessionId): bool
    {
        $session = $this->getSession($sessionId);
        
        if (!$session) {
            return false;
        }

        $session['last_activity'] = now()->toISOString();
        $session['request_count']++;

        Cache::put("mcp_session:{$sessionId}", $session, 3600);
        
        return true;
    }

    /**
     * 记录工具调用
     */
    public function logToolCall(string $sessionId, string $toolName, array $parameters, array $result): void
    {
        $session = $this->getSession($sessionId);
        
        if (!$session) {
            return;
        }

        $toolCall = [
            'timestamp' => now()->toISOString(),
            'tool_name' => $toolName,
            'parameters' => $parameters,
            'result' => $result,
            'success' => $result['success'] ?? false
        ];

        $session['tool_calls'][] = $toolCall;

        // 只保留最近50次工具调用
        if (count($session['tool_calls']) > 50) {
            $session['tool_calls'] = array_slice($session['tool_calls'], -50);
        }

        Cache::put("mcp_session:{$sessionId}", $session, 3600);

        $this->logger->info('MCP tool call logged', [
            'session_id' => $sessionId,
            'tool_name' => $toolName,
            'success' => $toolCall['success'],
            'agent_id' => $session['agent_id']
        ]);
    }

    /**
     * 记录资源访问
     */
    public function logResourceAccess(string $sessionId, string $resourceUri, array $result): void
    {
        $session = $this->getSession($sessionId);
        
        if (!$session) {
            return;
        }

        $resourceAccess = [
            'timestamp' => now()->toISOString(),
            'resource_uri' => $resourceUri,
            'result' => $result,
            'success' => $result['success'] ?? false
        ];

        $session['resource_accesses'][] = $resourceAccess;

        // 只保留最近50次资源访问
        if (count($session['resource_accesses']) > 50) {
            $session['resource_accesses'] = array_slice($session['resource_accesses'], -50);
        }

        Cache::put("mcp_session:{$sessionId}", $session, 3600);

        $this->logger->info('MCP resource access logged', [
            'session_id' => $sessionId,
            'resource_uri' => $resourceUri,
            'success' => $resourceAccess['success'],
            'agent_id' => $session['agent_id']
        ]);
    }

    /**
     * 记录错误
     */
    public function logError(string $sessionId, string $errorType, string $message, array $context = []): void
    {
        $session = $this->getSession($sessionId);
        
        if (!$session) {
            return;
        }

        $error = [
            'timestamp' => now()->toISOString(),
            'type' => $errorType,
            'message' => $message,
            'context' => $context
        ];

        $session['errors'][] = $error;

        // 只保留最近20个错误
        if (count($session['errors']) > 20) {
            $session['errors'] = array_slice($session['errors'], -20);
        }

        Cache::put("mcp_session:{$sessionId}", $session, 3600);

        $this->logger->error('MCP session error', [
            'session_id' => $sessionId,
            'error_type' => $errorType,
            'message' => $message,
            'context' => $context,
            'agent_id' => $session['agent_id']
        ]);
    }

    /**
     * 销毁会话
     */
    public function destroySession(string $sessionId): bool
    {
        $session = $this->getSession($sessionId);
        
        if (!$session) {
            return false;
        }

        Cache::forget("mcp_session:{$sessionId}");

        $this->logger->info('MCP session destroyed', [
            'session_id' => $sessionId,
            'agent_id' => $session['agent_id'],
            'duration' => now()->diffInSeconds($session['created_at']),
            'request_count' => $session['request_count'],
            'tool_calls' => count($session['tool_calls']),
            'resource_accesses' => count($session['resource_accesses']),
            'errors' => count($session['errors'])
        ]);

        return true;
    }

    /**
     * 获取会话统计信息
     */
    public function getSessionStats(string $sessionId): ?array
    {
        $session = $this->getSession($sessionId);
        
        if (!$session) {
            return null;
        }

        return [
            'session_id' => $sessionId,
            'agent_id' => $session['agent_id'],
            'user_id' => $session['user_id'],
            'created_at' => $session['created_at'],
            'last_activity' => $session['last_activity'],
            'duration_seconds' => now()->diffInSeconds($session['created_at']),
            'request_count' => $session['request_count'],
            'tool_calls_count' => count($session['tool_calls']),
            'resource_accesses_count' => count($session['resource_accesses']),
            'errors_count' => count($session['errors']),
            'success_rate' => $this->calculateSuccessRate($session)
        ];
    }

    /**
     * 计算成功率
     */
    private function calculateSuccessRate(array $session): float
    {
        $totalOperations = count($session['tool_calls']) + count($session['resource_accesses']);
        
        if ($totalOperations === 0) {
            return 100.0;
        }

        $successfulOperations = 0;
        
        foreach ($session['tool_calls'] as $call) {
            if ($call['success']) {
                $successfulOperations++;
            }
        }
        
        foreach ($session['resource_accesses'] as $access) {
            if ($access['success']) {
                $successfulOperations++;
            }
        }

        return round(($successfulOperations / $totalOperations) * 100, 2);
    }

    /**
     * 清理过期会话
     */
    public function cleanupExpiredSessions(): int
    {
        // 这个方法需要配合定时任务使用
        // 由于我们使用Cache存储，过期会话会自动清理
        // 这里主要用于记录清理日志
        
        $this->logger->info('MCP session cleanup completed');
        
        return 0; // 返回清理的会话数量
    }

    /**
     * 获取Agent的活跃会话
     */
    public function getAgentActiveSessions(string $agentId): array
    {
        // 由于使用Cache存储，无法直接查询所有会话
        // 这个功能需要配合数据库存储实现
        // 暂时返回空数组
        
        return [];
    }

    /**
     * 从请求中提取或创建会话ID
     */
    public function getOrCreateSessionFromRequest(\Illuminate\Http\Request $request, Agent $agent): string
    {
        // 尝试从请求头获取会话ID
        $sessionId = $request->header('X-MCP-Session-ID');
        
        if ($sessionId && $this->getSession($sessionId)) {
            $this->updateActivity($sessionId);
            return $sessionId;
        }

        // 创建新会话
        $context = [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'url' => $request->url(),
            'method' => $request->method()
        ];

        return $this->createSession($agent, $context);
    }
}
