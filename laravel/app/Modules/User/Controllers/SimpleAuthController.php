<?php

namespace App\Modules\User\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Modules\User\Services\SimpleAuthService;

class SimpleAuthController extends Controller
{
    protected SimpleAuthService $authService;

    public function __construct(SimpleAuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * 用户注册
     */
    public function register(Request $request): JsonResponse
    {
        try {
            $result = $this->authService->register($request->all());

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Registration successful',
            ], 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Registration failed: ' . $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    }

    /**
     * 用户登录
     */
    public function login(Request $request): JsonResponse
    {
        try {
            $result = $this->authService->login(
                $request->input('email'),
                $request->input('password'),
                $request->input('remember', false)
            );

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Login successful',
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Login failed: ' . $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    }

    /**
     * 用户登出
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            $this->authService->logout();

            return response()->json([
                'success' => true,
                'message' => 'Logout successful',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Logout failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 获取当前用户信息
     */
    public function me(Request $request): JsonResponse
    {
        try {
            $user = $this->authService->getCurrentUser();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthenticated',
                ], 401);
            }

            return response()->json([
                'success' => true,
                'data' => $user,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to get user information: ' . $e->getMessage(),
            ], 500);
        }
    }
}
