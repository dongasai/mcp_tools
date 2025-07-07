<?php

namespace App\Modules\Mcp\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

class ToolController extends Controller
{
    /**
     * 获取工具列表
     */
    public function list(Request $request): JsonResponse
    {
        // TODO: 实现工具列表获取逻辑
        return response()->json([
            'success' => true,
            'data' => [
                'tools' => [],
                'message' => 'MCP Tool Controller - 待实现'
            ]
        ]);
    }

    /**
     * 调用指定工具
     */
    public function call(Request $request, string $tool): JsonResponse
    {
        // TODO: 实现工具调用逻辑
        return response()->json([
            'success' => true,
            'data' => [
                'tool' => $tool,
                'result' => null,
                'message' => 'MCP Tool Call - 待实现'
            ]
        ]);
    }
}
