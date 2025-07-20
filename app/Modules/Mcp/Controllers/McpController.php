<?php

namespace App\Modules\Mcp\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use App\Modules\Mcp\Services\McpService;
use App\Modules\Agent\Services\AgentService;

class McpController extends Controller
{
    public function __construct(
        private McpService $mcpService,
        private AgentService $agentService
    ) {}

    /**
     * 获取MCP服务器信息
     */
    public function info(): JsonResponse
    {
        return response()->json([
            'name' => config('mcp.server.name'),
            'version' => config('mcp.server.version'),
            'transport' => config('mcp.server.transport'),
            'capabilities' => config('mcp.capabilities'),
            'description' => 'MCP Tools Server - Model Context Protocol implementation for project and task management',
        ]);
    }

    /**
     * 获取服务器能力
     */
    public function capabilities(): JsonResponse
    {
        return response()->json([
            'capabilities' => config('mcp.capabilities'),
            'resources' => array_keys(config('mcp.resources', [])),
            'tools' => array_keys(config('mcp.tools', [])),
        ]);
    }

    /**
     * 获取服务器状态
     */
    public function status(): JsonResponse
    {
        return response()->json($this->mcpService->getServerStatus());
    }

    /**
     * 开始MCP会话
     */
    public function startSession(Request $request): JsonResponse
    {
        $agentId = $request->header('X-Agent-ID');
        $token = $request->header('X-Agent-Token');

        if (!$agentId || !$token) {
            return response()->json([
                'error' => 'Missing agent credentials'
            ], 401);
        }

        // 验证Agent
        $agent = $this->agentService->findByIdentifier($agentId);
        if (!$agent || !$this->agentService->validateToken($agent, $token)) {
            return response()->json([
                'error' => 'Invalid agent credentials'
            ], 401);
        }

        // 记录会话开始
        $this->mcpService->logSession($agentId, 'session_start');

        return response()->json([
            'session_id' => uniqid('mcp_session_'),
            'agent_id' => $agentId,
            'started_at' => now()->toISOString(),
            'expires_at' => now()->addSeconds(config('mcp.auth.session_timeout'))->toISOString(),
        ]);
    }

    /**
     * 结束MCP会话
     */
    public function endSession(Request $request): JsonResponse
    {
        $agentId = $request->header('X-Agent-ID');
        
        if ($agentId) {
            $this->mcpService->logSession($agentId, 'session_end');
        }

        return response()->json([
            'message' => 'Session ended successfully'
        ]);
    }

    /**
     * 获取会话状态
     */
    public function sessionStatus(Request $request): JsonResponse
    {
        $agentId = $request->header('X-Agent-ID');
        
        if (!$agentId) {
            return response()->json([
                'error' => 'Missing agent ID'
            ], 400);
        }

        $agent = $this->agentService->findByIdentifier($agentId);
        
        return response()->json([
            'agent_id' => $agentId,
            'agent_status' => $agent ? $agent->status : 'unknown',
            'session_active' => $agent && $agent->status === 'active',
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * SSE事件流
     */
    public function sseEvents(Request $request): Response
    {
        $agentId = $request->header('X-Agent-ID');
        
        if (!$agentId) {
            return response('Missing agent ID', 400);
        }

        return response()->stream(function () use ($agentId) {
            // 设置SSE头部
            echo "data: " . json_encode([
                'type' => 'connection',
                'message' => 'Connected to MCP server',
                'agent_id' => $agentId,
                'timestamp' => now()->toISOString()
            ]) . "\n\n";
            
            // 刷新输出
            if (ob_get_level()) {
                ob_flush();
            }
            flush();

            // 保持连接活跃
            while (true) {
                // 发送心跳
                echo "data: " . json_encode([
                    'type' => 'heartbeat',
                    'timestamp' => now()->toISOString()
                ]) . "\n\n";
                
                if (ob_get_level()) {
                    ob_flush();
                }
                flush();
                
                // 等待30秒
                sleep(30);
                
                // 检查连接是否仍然活跃
                if (connection_aborted()) {
                    break;
                }
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Headers' => 'Cache-Control',
        ]);
    }

    /**
     * 发送SSE消息
     */
    public function sseSend(Request $request): JsonResponse
    {
        $agentId = $request->header('X-Agent-ID');
        $message = $request->input('message');
        $type = $request->input('type', 'message');

        if (!$agentId || !$message) {
            return response()->json([
                'error' => 'Missing required parameters'
            ], 400);
        }

        // 记录消息发送
        $this->mcpService->logSession($agentId, 'sse_send', [
            'type' => $type,
            'message' => $message
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Message sent successfully'
        ]);
    }
}
