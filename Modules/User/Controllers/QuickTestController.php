<?php

namespace Modules\User\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\User\Models\User;
use Illuminate\Support\Facades\Hash;

class QuickTestController extends Controller
{
    /**
     * 快速注册测试
     */
    public function quickRegister(Request $request): JsonResponse
    {
        try {
            $data = $request->all();
            
            // 简单验证
            if (empty($data['name']) || empty($data['email']) || empty($data['password'])) {
                return response()->json([
                    'success' => false,
                    'error' => 'Name, email and password are required',
                ], 400);
            }

            // 检查邮箱是否已存在
            if (User::where('email', $data['email'])->exists()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Email already exists',
                ], 400);
            }

            // 创建用户
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'role' => 'user',
                'status' => 'active',
                'settings' => json_encode([]),
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'user' => $user->makeHidden(['password']),
                    'message' => 'User registered successfully',
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Registration failed: ' . $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    }

    /**
     * 快速登录测试
     */
    public function quickLogin(Request $request): JsonResponse
    {
        try {
            $data = $request->all();
            
            // 简单验证
            if (empty($data['email']) || empty($data['password'])) {
                return response()->json([
                    'success' => false,
                    'error' => 'Email and password are required',
                ], 400);
            }

            // 查找用户
            $user = User::where('email', $data['email'])->first();
            
            if (!$user || !Hash::check($data['password'], $user->password)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid credentials',
                ], 401);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'user' => $user->makeHidden(['password']),
                    'message' => 'Login successful',
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Login failed: ' . $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    }

    /**
     * 获取所有用户
     */
    public function getUsers(): JsonResponse
    {
        try {
            $users = User::all()->makeHidden(['password']);
            
            return response()->json([
                'success' => true,
                'data' => $users,
                'count' => $users->count(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to get users: ' . $e->getMessage(),
            ], 500);
        }
    }
}
