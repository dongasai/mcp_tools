<?php

namespace App\Modules\Mcp\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Modules\Mcp\Services\McpService;


class McpTestController extends Controller
{
    public function __construct(
        private McpService $mcpService
    ) {}

    /**
     * 测试MCP模块功能
     */
    public function testMcpFunctions(Request $request): JsonResponse
    {
        try {
            $results = [];

            // 测试基础配置
            $results['config_test'] = $this->testMcpConfig();

            // 测试服务状态
            $results['service_test'] = $this->testMcpService();

            return response()->json([
                'success' => true,
                'message' => 'MCP functions test completed',
                'results' => $results
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Test failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 测试MCP配置
     */
    private function testMcpConfig(): array
    {
        try {
            $config = [
                'resources' => config('mcp.resources', []),
                'tools' => config('mcp.tools', []),
                'capabilities' => config('mcp.capabilities', []),
                'server' => config('mcp.server', [])
            ];

            return [
                'status' => 'success',
                'data' => $config
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * 测试MCP服务
     */
    private function testMcpService(): array
    {
        try {
            $status = $this->mcpService->getServerStatus();

            return [
                'status' => 'success',
                'data' => $status
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * 获取MCP模块状态
     */
    public function getStatus(): JsonResponse
    {
        try {
            $status = [
                'mcp_service' => 'available',
                'resource_controller' => 'implemented',
                'tool_controller' => 'implemented',
                'available_resources' => array_keys(config('mcp.resources', [])),
                'available_tools' => array_keys(config('mcp.tools', [])),
                'server_status' => $this->mcpService->getServerStatus()
            ];

            return response()->json([
                'success' => true,
                'status' => $status
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to get status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 测试特定工具
     */
    public function testTool(Request $request, string $toolName): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'tool' => $toolName,
                'message' => 'Tool testing not implemented yet - controllers need dependency injection fixes'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Tool test failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 测试特定资源
     */
    public function testResource(Request $request, string $resourceUri): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'resource' => $resourceUri,
                'message' => 'Resource testing not implemented yet - controllers need dependency injection fixes'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Resource test failed: ' . $e->getMessage()
            ], 500);
        }
    }
}
