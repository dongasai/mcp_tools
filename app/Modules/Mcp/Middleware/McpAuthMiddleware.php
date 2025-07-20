<?php

namespace App\Modules\Mcp\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Modules\Agent\Services\AuthenticationService;
use App\Modules\Core\Services\LogService;
use App\Modules\Mcp\Services\SessionService;
use PhpMcp\Schema\JsonRpc\Error as JsonRpcError;
use Symfony\Component\HttpFoundation\Response;

class McpAuthMiddleware
{
    public function __construct(
        private AuthenticationService $authService,
        private LogService $logger,
        private SessionService $sessionService
    ) {}

    /**
     * Handle an incoming MCP request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            // 提取认证信息
            $authInfo = $this->authService->extractAuthFromRequest($request);

            if (!$authInfo['token']) {
                return $this->unauthorizedResponse('MCP access token required');
            }

            // 认证Agent - 如果没有提供agent_id，尝试通过token查找
            if ($authInfo['agent_id']) {
                $agent = $this->authService->authenticate($authInfo['token'], $authInfo['agent_id']);
            } else {
                // 通过token查找agent（向后兼容）
                $agent = $this->authService->authenticateByTokenOnly($authInfo['token']);
            }

            if (!$agent) {
                return $this->unauthorizedResponse('Invalid MCP access token or agent ID');
            }

            // 更新最后活跃时间
            $this->authService->updateLastActive($agent);

            // 获取或创建MCP会话
            $sessionId = $this->sessionService->getOrCreateSessionFromRequest($request, $agent);

            // 将Agent和会话信息添加到请求中
            $request->attributes->set('mcp_agent', $agent);
            $request->attributes->set('mcp_agent_id', $agent->identifier);
            $request->attributes->set('mcp_user_id', $agent->user_id);
            $request->attributes->set('mcp_session_id', $sessionId);

            // 记录MCP访问日志
            $this->logger->info('MCP request authenticated', [
                'agent_id' => $agent->identifier,
                'user_id' => $agent->user_id,
                'session_id' => $sessionId,
                'route' => $request->route()?->getName(),
                'method' => $request->method(),
                'url' => $request->url(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

            return $next($request);

        } catch (\Exception $e) {
            $this->logger->error('MCP authentication error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'url' => $request->url(),
                'method' => $request->method(),
                'ip' => $request->ip()
            ]);

            return $this->errorResponse('MCP authentication error occurred');
        }
    }

    /**
     * 返回未授权响应
     */
    private function unauthorizedResponse(string $message): JsonResponse
    {
        $error = JsonRpcError::forInvalidRequest($message);
        
        return response()->json($error->toArray(), 401);
    }

    /**
     * 返回错误响应
     */
    private function errorResponse(string $message): JsonResponse
    {
        $error = JsonRpcError::forInternalError($message);
        
        return response()->json($error->toArray(), 500);
    }
}