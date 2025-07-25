<?php

namespace App\Modules\Agent\Services;

use App\Modules\Agent\Models\Agent;
use App\Modules\User\Models\User;
use App\Modules\Core\Services\LogService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class AuthenticationService
{
    public function __construct(
        private LogService $logger
    ) {}

    /**
     * 通过访问令牌认证Agent
     */
    public function authenticate(string $token, string $agentId): ?Agent
    {
        try {
            // 从缓存中查找令牌
            $cacheKey = "agent_token:{$token}";
            $cachedAgentId = Cache::get($cacheKey);
            
            if ($cachedAgentId) {
                $agent = Agent::find($cachedAgentId);
                if ($agent && $this->validateAgent($agent, $token, $agentId)) {
                    return $agent;
                }
            }

            // 从数据库查找
            $agent = Agent::where('access_token', $token)->first();
            
            if (!$agent) {
                $this->logger->warning('Agent authentication failed: token not found', [
                    'token' => substr($token, 0, 10) . '...',
                    'agent_id' => $agentId
                ]);
                return null;
            }

            if (!$this->validateAgent($agent, $token, $agentId)) {
                return null;
            }

            // 缓存有效的令牌
            $ttl = $agent->token_expires_at ? $agent->token_expires_at->diffInSeconds(now()) : 3600;
            Cache::put($cacheKey, $agent->id, min($ttl, 3600));

            return $agent;

        } catch (\Exception $e) {
            $this->logger->error('Agent authentication error', [
                'token' => substr($token, 0, 10) . '...',
                'agent_id' => $agentId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * 仅通过访问令牌认证Agent（向后兼容方法）
     */
    public function authenticateByTokenOnly(string $token): ?Agent
    {
        try {
            // 从缓存中查找令牌
            $cacheKey = "agent_token:{$token}";
            $cachedAgentId = Cache::get($cacheKey);

            if ($cachedAgentId) {
                $agent = Agent::find($cachedAgentId);
                if ($agent && $this->validateAgentByTokenOnly($agent, $token)) {
                    return $agent;
                }
            }

            // 从数据库查找
            $agent = Agent::where('access_token', $token)->first();

            if (!$agent) {
                $this->logger->warning('Agent authentication failed: token not found', [
                    'token' => substr($token, 0, 10) . '...'
                ]);
                return null;
            }

            if (!$this->validateAgentByTokenOnly($agent, $token)) {
                return null;
            }

            // 缓存有效的令牌
            $ttl = $agent->token_expires_at ? $agent->token_expires_at->diffInSeconds(now()) : 3600;
            Cache::put($cacheKey, $agent->id, min($ttl, 3600));

            return $agent;

        } catch (\Exception $e) {
            $this->logger->error('Token-only authentication error', [
                'token' => substr($token, 0, 10) . '...',
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * 验证Agent状态和令牌
     */
    private function validateAgent(Agent $agent, string $token, string $agentId): bool
    {
        // 检查令牌是否匹配
        if ($agent->access_token !== $token) {
            $this->logger->warning('Agent token mismatch', [
                'agent_id' => $agent->identifier,
                'provided_agent_id' => $agentId
            ]);
            return false;
        }

        // 检查Agent ID是否匹配（必须提供且匹配）
        if (!$agentId) {
            $this->logger->warning('Agent ID not provided', [
                'agent_id' => $agent->identifier
            ]);
            return false;
        }

        if ($agent->identifier !== $agentId) {
            $this->logger->warning('Agent ID mismatch', [
                'expected' => $agent->identifier,
                'provided' => $agentId
            ]);
            return false;
        }

        // 检查令牌是否过期
        if ($agent->isTokenExpired()) {
            $this->logger->warning('Agent token expired', [
                'agent_id' => $agent->agent_id,
                'expired_at' => $agent->token_expires_at
            ]);
            return false;
        }

        // 检查Agent状态
        if ($agent->status !== 'active') {
            $this->logger->warning('Agent is not active', [
                'agent_id' => $agent->identifier,
                'status' => $agent->status
            ]);
            return false;
        }

        return true;
    }

    /**
     * 验证Agent状态和令牌（仅验证token，不验证agent_id）
     */
    private function validateAgentByTokenOnly(Agent $agent, string $token): bool
    {
        // 检查令牌是否匹配
        if ($agent->access_token !== $token) {
            $this->logger->warning('Agent token mismatch', [
                'agent_id' => $agent->identifier
            ]);
            return false;
        }

        // 检查令牌是否过期
        if ($agent->isTokenExpired()) {
            $this->logger->warning('Agent token expired', [
                'agent_id' => $agent->identifier,
                'expired_at' => $agent->token_expires_at
            ]);
            return false;
        }

        // 检查Agent状态
        if ($agent->status !== 'active') {
            $this->logger->warning('Agent is not active', [
                'agent_id' => $agent->identifier,
                'status' => $agent->status
            ]);
            return false;
        }

        return true;
    }

    /**
     * 通过Agent ID查找Agent
     */
    public function findByAgentId(string $agentId): ?Agent
    {
        return Agent::where('identifier', $agentId)->first();
    }

    /**
     * 验证令牌有效性（仅验证token，不验证agentId）
     */
    public function validateToken(string $token): bool
    {
        try {
            $agent = Agent::where('access_token', $token)->first();

            if (!$agent) {
                return false;
            }

            // 检查令牌是否过期
            if ($agent->isTokenExpired()) {
                return false;
            }

            // 检查Agent状态
            if ($agent->status !== 'active') {
                return false;
            }

            return true;

        } catch (\Exception $e) {
            $this->logger->error('Token validation error', [
                'token' => substr($token, 0, 10) . '...',
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * 刷新Agent令牌
     */
    public function refreshToken(Agent $agent): string
    {
        // 清除旧令牌缓存
        if ($agent->access_token) {
            Cache::forget("agent_token:{$agent->access_token}");
        }

        // 生成新令牌
        $newToken = $agent->generateAccessToken();

        $this->logger->audit('agent_token_refreshed', $agent->user_id, [
            'agent_id' => $agent->identifier,
            'old_token' => substr($agent->getOriginal('access_token') ?? '', 0, 10) . '...',
            'new_token' => substr($newToken, 0, 10) . '...',
            'expires_at' => $agent->token_expires_at
        ]);

        return $newToken;
    }

    /**
     * 撤销Agent令牌
     */
    public function revokeToken(Agent $agent): bool
    {
        try {
            // 清除缓存
            if ($agent->access_token) {
                Cache::forget("agent_token:{$agent->access_token}");
            }

            // 清除数据库中的令牌
            $agent->access_token = null;
            $agent->token_expires_at = null;
            $agent->save();

            $this->logger->audit('agent_token_revoked', $agent->user_id, [
                'agent_id' => $agent->identifier
            ]);

            return true;

        } catch (\Exception $e) {
            $this->logger->error('Failed to revoke agent token', [
                'agent_id' => $agent->identifier,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * 检查令牌是否过期
     */
    public function isTokenExpired(string $token): bool
    {
        $agent = Agent::where('access_token', $token)->first();
        return $agent ? $agent->isTokenExpired() : true;
    }

    /**
     * 获取令牌信息
     */
    public function getTokenInfo(string $token): array
    {
        $agent = Agent::where('access_token', $token)->first();
        
        if (!$agent) {
            return [
                'valid' => false,
                'error' => 'Token not found'
            ];
        }

        return [
            'valid' => !$agent->isTokenExpired() && $agent->status === 'active',
            'agent_id' => $agent->identifier,
            'agent_name' => $agent->name,
            'user_id' => $agent->user_id,
            'status' => $agent->status,
            'expires_at' => $agent->token_expires_at?->toISOString(),
            'last_active_at' => $agent->last_active_at?->toISOString(),
            'allowed_projects' => $agent->allowed_projects ?? [],
            'allowed_actions' => $agent->allowed_actions ?? []
        ];
    }

    /**
     * 更新Agent最后活跃时间
     */
    public function updateLastActive(Agent $agent): void
    {
        $agent->updateLastActive();
    }

    /**
     * 从请求中提取认证信息
     */
    public function extractAuthFromRequest(\Illuminate\Http\Request $request): array
    {
        // 优先从Authorization头获取Bearer token
        $token = $request->bearerToken();
        
        // 如果没有Bearer token，尝试从X-Agent-Token头获取
        if (!$token) {
            $token = $request->header('X-Agent-Token');
        }
        
        // 最后尝试从查询参数获取
        if (!$token) {
            $token = $request->query('token');
        }

        // 获取Agent ID
        $agentId = $request->header('X-Agent-ID') ?: $request->query('agent_id');

        return [
            'token' => $token,
            'agent_id' => $agentId
        ];
    }

    /**
     * 生成Agent访问令牌
     */
    public function generateTokenForAgent(Agent $agent): string
    {
        return $agent->generateAccessToken();
    }

    /**
     * 批量撤销用户的所有Agent令牌
     */
    public function revokeAllUserTokens(User $user): int
    {
        $agents = Agent::where('user_id', $user->id)->whereNotNull('access_token')->get();
        $revokedCount = 0;

        foreach ($agents as $agent) {
            if ($this->revokeToken($agent)) {
                $revokedCount++;
            }
        }

        $this->logger->audit('user_agent_tokens_revoked', $user->id, [
            'revoked_count' => $revokedCount,
            'total_agents' => $agents->count()
        ]);

        return $revokedCount;
    }
}
