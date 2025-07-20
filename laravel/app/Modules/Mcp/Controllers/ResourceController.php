<?php

namespace App\Modules\Mcp\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Modules\Mcp\Services\McpService;
use App\Modules\Core\Services\LogService;
use App\Modules\Mcp\Resources\ProjectResource;
use App\Modules\Mcp\Resources\TaskResource;

class ResourceController extends Controller
{
    public function __construct(
        private McpService $mcpService,
        private LogService $logger,
        private ProjectResource $projectResource,
        private TaskResource $taskResource
    ) {}

    /**
     * 获取资源列表
     */
    public function list(Request $request): JsonResponse
    {
        try {
            $agentId = $request->attributes->get('mcp_agent_id');

            // 记录操作
            $this->mcpService->logSession($agentId, 'resource_list');

            // 获取配置的资源列表
            $resources = config('mcp.resources', []);

            $resourceList = [];
            foreach ($resources as $name => $config) {
                $resourceList[] = [
                    'name' => $name,
                    'description' => $config['description'] ?? '',
                    'uri_template' => $config['uri_template'] ?? '',
                    'class' => $config['class'] ?? ''
                ];
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'resources' => $resourceList,
                    'count' => count($resourceList)
                ]
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to list resources', [
                'error' => $e->getMessage(),
                'agent_id' => $request->attributes->get('mcp_agent_id')
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to list resources: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 读取指定资源
     */
    public function read(Request $request, string $resource): JsonResponse
    {
        try {
            $agentId = $request->attributes->get('mcp_agent_id');

            // 验证权限
            if (!$this->mcpService->validateAgentAccess($agentId, 'resource', 'read')) {
                return response()->json([
                    'success' => false,
                    'error' => 'Access denied for resource read'
                ], 403);
            }

            // 记录操作
            $this->mcpService->logSession($agentId, 'resource_read', [
                'resource' => $resource
            ]);

            // 解析资源URI
            $resourceData = $this->parseResourceUri($resource);

            if (!$resourceData) {
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid resource URI: ' . $resource
                ], 400);
            }

            // 根据资源类型分发到对应的资源处理器
            $result = $this->dispatchResourceRead($resourceData['type'], $resourceData['uri']);

            return response()->json([
                'success' => true,
                'data' => $result
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to read resource', [
                'resource' => $resource,
                'error' => $e->getMessage(),
                'agent_id' => $request->attributes->get('mcp_agent_id')
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to read resource: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 创建资源
     */
    public function create(Request $request, string $resource): JsonResponse
    {
        try {
            $agentId = $request->attributes->get('mcp_agent_id');

            // 验证权限
            if (!$this->mcpService->validateAgentAccess($agentId, 'resource', 'create')) {
                return response()->json([
                    'success' => false,
                    'error' => 'Access denied for resource create'
                ], 403);
            }

            // 记录操作
            $this->mcpService->logSession($agentId, 'resource_create', [
                'resource' => $resource,
                'data' => $request->all()
            ]);

            // 解析资源URI
            $resourceData = $this->parseResourceUri($resource);

            if (!$resourceData) {
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid resource URI: ' . $resource
                ], 400);
            }

            // 根据资源类型分发到对应的资源处理器
            $result = $this->dispatchResourceCreate($resourceData['type'], $resourceData['uri'], $request->all());

            return response()->json([
                'success' => true,
                'data' => $result
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to create resource', [
                'resource' => $resource,
                'error' => $e->getMessage(),
                'agent_id' => $request->attributes->get('mcp_agent_id')
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to create resource: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 更新资源
     */
    public function update(Request $request, string $resource): JsonResponse
    {
        try {
            $agentId = $request->attributes->get('mcp_agent_id');

            // 验证权限
            if (!$this->mcpService->validateAgentAccess($agentId, 'resource', 'update')) {
                return response()->json([
                    'success' => false,
                    'error' => 'Access denied for resource update'
                ], 403);
            }

            // 记录操作
            $this->mcpService->logSession($agentId, 'resource_update', [
                'resource' => $resource,
                'data' => $request->all()
            ]);

            // 解析资源URI
            $resourceData = $this->parseResourceUri($resource);

            if (!$resourceData) {
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid resource URI: ' . $resource
                ], 400);
            }

            // 根据资源类型分发到对应的资源处理器
            $result = $this->dispatchResourceUpdate($resourceData['type'], $resourceData['uri'], $request->all());

            return response()->json([
                'success' => true,
                'data' => $result
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to update resource', [
                'resource' => $resource,
                'error' => $e->getMessage(),
                'agent_id' => $request->attributes->get('mcp_agent_id')
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to update resource: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 删除资源
     */
    public function delete(Request $request, string $resource): JsonResponse
    {
        try {
            $agentId = $request->attributes->get('mcp_agent_id');

            // 验证权限
            if (!$this->mcpService->validateAgentAccess($agentId, 'resource', 'delete')) {
                return response()->json([
                    'success' => false,
                    'error' => 'Access denied for resource delete'
                ], 403);
            }

            // 记录操作
            $this->mcpService->logSession($agentId, 'resource_delete', [
                'resource' => $resource
            ]);

            // 解析资源URI
            $resourceData = $this->parseResourceUri($resource);

            if (!$resourceData) {
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid resource URI: ' . $resource
                ], 400);
            }

            // 根据资源类型分发到对应的资源处理器
            $result = $this->dispatchResourceDelete($resourceData['type'], $resourceData['uri']);

            return response()->json([
                'success' => true,
                'data' => $result
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to delete resource', [
                'resource' => $resource,
                'error' => $e->getMessage(),
                'agent_id' => $request->attributes->get('mcp_agent_id')
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to delete resource: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 解析资源URI
     */
    private function parseResourceUri(string $resource): ?array
    {
        // 支持的资源URI格式：
        // project://list, project://123
        // task://list, task://detail/123, task://assigned/agent123, task://status/pending

        if (str_contains($resource, '://')) {
            [$type, $path] = explode('://', $resource, 2);
            return [
                'type' => $type,
                'uri' => $resource,
                'path' => $path
            ];
        }

        // 如果没有协议前缀，尝试从路径推断
        if (str_starts_with($resource, 'project/') || str_starts_with($resource, 'projects/')) {
            return [
                'type' => 'project',
                'uri' => 'project://' . str_replace(['project/', 'projects/'], '', $resource),
                'path' => str_replace(['project/', 'projects/'], '', $resource)
            ];
        }

        if (str_starts_with($resource, 'task/') || str_starts_with($resource, 'tasks/')) {
            return [
                'type' => 'task',
                'uri' => 'task://' . str_replace(['task/', 'tasks/'], '', $resource),
                'path' => str_replace(['task/', 'tasks/'], '', $resource)
            ];
        }

        return null;
    }

    /**
     * 分发资源读取请求
     */
    private function dispatchResourceRead(string $type, string $uri): array
    {
        return match ($type) {
            'project' => $this->projectResource->read($uri),
            'task' => $this->taskResource->read($uri),
            default => throw new \Exception('Unsupported resource type: ' . $type)
        };
    }

    /**
     * 分发资源创建请求
     */
    private function dispatchResourceCreate(string $type, string $uri, array $data): array
    {
        return match ($type) {
            'project' => $this->createProjectResource($uri, $data),
            'task' => $this->createTaskResource($uri, $data),
            default => throw new \Exception('Unsupported resource type: ' . $type)
        };
    }

    /**
     * 分发资源更新请求
     */
    private function dispatchResourceUpdate(string $type, string $uri, array $data): array
    {
        return match ($type) {
            'project' => $this->updateProjectResource($uri, $data),
            'task' => $this->updateTaskResource($uri, $data),
            default => throw new \Exception('Unsupported resource type: ' . $type)
        };
    }

    /**
     * 分发资源删除请求
     */
    private function dispatchResourceDelete(string $type, string $uri): array
    {
        return match ($type) {
            'project' => $this->deleteProjectResource($uri),
            'task' => $this->deleteTaskResource($uri),
            default => throw new \Exception('Unsupported resource type: ' . $type)
        };
    }

    /**
     * 创建项目资源
     */
    private function createProjectResource(string $uri, array $data): array
    {
        // 项目资源创建逻辑
        return [
            'message' => 'Project resource creation not implemented yet',
            'uri' => $uri,
            'data' => $data
        ];
    }

    /**
     * 更新项目资源
     */
    private function updateProjectResource(string $uri, array $data): array
    {
        // 项目资源更新逻辑
        return [
            'message' => 'Project resource update not implemented yet',
            'uri' => $uri,
            'data' => $data
        ];
    }

    /**
     * 删除项目资源
     */
    private function deleteProjectResource(string $uri): array
    {
        // 项目资源删除逻辑
        return [
            'message' => 'Project resource deletion not implemented yet',
            'uri' => $uri
        ];
    }

    /**
     * 创建任务资源
     */
    private function createTaskResource(string $uri, array $data): array
    {
        // 任务资源创建逻辑
        return [
            'message' => 'Task resource creation not implemented yet',
            'uri' => $uri,
            'data' => $data
        ];
    }

    /**
     * 更新任务资源
     */
    private function updateTaskResource(string $uri, array $data): array
    {
        // 任务资源更新逻辑
        return [
            'message' => 'Task resource update not implemented yet',
            'uri' => $uri,
            'data' => $data
        ];
    }

    /**
     * 删除任务资源
     */
    private function deleteTaskResource(string $uri): array
    {
        // 任务资源删除逻辑
        return [
            'message' => 'Task resource deletion not implemented yet',
            'uri' => $uri
        ];
    }
}
