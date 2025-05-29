<?php

namespace App\Modules\User\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Modules\Core\Validators\SimpleValidator;

class TestController extends Controller
{
    /**
     * 测试简单响应
     */
    public function simple(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'User module test endpoint working',
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * 测试简单POST请求
     */
    public function simplePost(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'POST request working',
            'data' => $request->all(),
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * 测试验证器
     */
    public function testValidator(Request $request): JsonResponse
    {
        try {
            $data = $request->all();

            // 直接使用Laravel验证器
            $validator = \Illuminate\Support\Facades\Validator::make($data, [
                'name' => 'required|string|min:2|max:255',
                'email' => 'required|email',
                'password' => 'required|string|min:8',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors(),
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Validation passed',
                'validated_data' => $validator->validated(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ], 400);
        }
    }

    /**
     * 测试数据库连接
     */
    public function testDatabase(): JsonResponse
    {
        try {
            $userCount = \App\Modules\User\Models\User::count();

            return response()->json([
                'success' => true,
                'message' => 'Database connection working',
                'user_count' => $userCount,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    }
}
