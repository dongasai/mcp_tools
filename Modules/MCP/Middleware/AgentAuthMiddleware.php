<?php

namespace Modules\MCP\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Modules\MCP\Services\AuthenticationService;
use Modules\MCP\Services\AuthorizationService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;

class AgentAuthMiddleware
{
    public function __construct(
        private AuthenticationService $authService,
        private AuthorizationService $authzService,
        private LoggerInterface $logger
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        try {
            // 提取认证信息
            $authInfo = $this->authService->extractAuthFromRequest($request);
            
            if (!$authInfo['token']) {
                return $this->unauthorizedResponse('Access token required');
            }

            // 认证Agent
            $agent = $this->authService->authenticate($authInfo['token'], $authInfo['agent_id']);
            
            if (!$agent) {
                return $this->unauthorizedResponse('Invalid access token or agent ID');
            }

            // 更新最后活跃时间
            $this->authService->updateLastActive($agent);

            // 将Agent信息添加到请求中
            $request->attributes->set('agent', $agent);
            $request->attributes->set('agent_id', $agent->agent_id);
            $request->attributes->set('user_id', $agent->user_id);

            // 检查权限（如果指定了权限要求）
            if (!empty($permissions)) {
                foreach ($permissions as $permission) {
                    if (!$this->authzService->canPerformAction($agent, $permission)) {
                        $this->logger->warning('Agent permission denied', [
                            'agent_id' => $agent->agent_id,
                            'permission' => $permission,
                            'route' => $request->route()?->getName(),
                            'url' => $request->url()
                        ]);
                        
                        return $this->forbiddenResponse("Permission denied: {$permission}");
                    }
                }
            }

            // 记录访问日志
            $this->logger->info('Agent authenticated successfully', [
                'agent_id' => $agent->agent_id,
                'user_id' => $agent->user_id,
                'route' => $request->route()?->getName(),
                'method' => $request->method(),
                'url' => $request->url(),
                'ip' => $request->ip()
            ]);

            return $next($request);

        } catch (\Exception $e) {
            $this->logger->error('Agent authentication error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'url' => $request->url(),
                'method' => $request->method()
            ]);

            return $this->errorResponse('Authentication error occurred');
        }
    }

    /**
     * 返回未授权响应
     */
    private function unauthorizedResponse(string $message): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => $message,
            'code' => 'UNAUTHORIZED'
        ], 401);
    }

    /**
     * 返回禁止访问响应
     */
    private function forbiddenResponse(string $message): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => $message,
            'code' => 'FORBIDDEN'
        ], 403);
    }

    /**
     * 返回错误响应
     */
    private function errorResponse(string $message): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => $message,
            'code' => 'INTERNAL_ERROR'
        ], 500);
    }
}
