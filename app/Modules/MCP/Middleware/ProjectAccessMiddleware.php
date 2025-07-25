<?php

namespace App\Modules\MCP\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Modules\MCP\Services\AuthorizationService;
use App\Modules\Core\Services\LogService;
use Symfony\Component\HttpFoundation\Response;

class ProjectAccessMiddleware
{
    public function __construct(
        private AuthorizationService $authzService,
        private LogService $logger
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $projectParam = 'projectId'): Response
    {
        try {
            // 获取Agent（应该已经通过AgentAuthMiddleware认证）
            $agent = $request->attributes->get('agent');
            
            if (!$agent) {
                return $this->errorResponse('Agent not authenticated');
            }

            // 获取项目ID
            $projectId = $this->extractProjectId($request, $projectParam);
            
            if (!$projectId) {
                return $this->badRequestResponse("Project ID parameter '{$projectParam}' is required");
            }

            // 检查项目访问权限
            if (!$this->authzService->canAccessProject($agent, $projectId)) {
                $this->logger->warning('Agent project access denied', [
                    'agent_id' => $agent->agent_id,
                    'project_id' => $projectId,
                    'route' => $request->route()?->getName(),
                    'url' => $request->url()
                ]);
                
                return $this->forbiddenResponse("Access denied to project {$projectId}");
            }

            // 将项目ID添加到请求属性中
            $request->attributes->set('project_id', $projectId);

            $this->logger->info('Agent project access granted', [
                'agent_id' => $agent->agent_id,
                'project_id' => $projectId,
                'route' => $request->route()?->getName()
            ]);

            return $next($request);

        } catch (\Exception $e) {
            $this->logger->error('Project access middleware error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'url' => $request->url(),
                'method' => $request->method()
            ]);

            return $this->errorResponse('Project access validation error');
        }
    }

    /**
     * 从请求中提取项目ID
     */
    private function extractProjectId(Request $request, string $projectParam): ?int
    {
        // 尝试从路由参数获取
        $projectId = $request->route($projectParam);
        
        // 如果路由参数没有，尝试从请求参数获取
        if (!$projectId) {
            $projectId = $request->input($projectParam);
        }
        
        // 如果还没有，尝试从查询参数获取
        if (!$projectId) {
            $projectId = $request->query($projectParam);
        }

        // 如果参数名是project_id，也尝试获取
        if (!$projectId && $projectParam !== 'project_id') {
            $projectId = $request->route('project_id') 
                      ?: $request->input('project_id') 
                      ?: $request->query('project_id');
        }

        return $projectId ? (int) $projectId : null;
    }

    /**
     * 返回禁止访问响应
     */
    private function forbiddenResponse(string $message): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => $message,
            'code' => 'PROJECT_ACCESS_DENIED'
        ], 403);
    }

    /**
     * 返回错误请求响应
     */
    private function badRequestResponse(string $message): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => $message,
            'code' => 'BAD_REQUEST'
        ], 400);
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
