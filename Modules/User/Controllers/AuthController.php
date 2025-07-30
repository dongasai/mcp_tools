<?php

namespace Modules\User\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\User\Services\AuthService;
use DLaravel\Contracts\LogInterface;

class AuthController extends Controller
{
    protected AuthService $authService;
    protected LogInterface $logger;

    public function __construct(AuthService $authService, LogInterface $logger)
    {
        $this->authService = $authService;
        $this->logger = $logger;
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
            $this->logger->error('Registration failed', [
                'error' => $e->getMessage(),
                'data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Registration failed',
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
            $this->logger->security('Login failed', [
                'email' => $request->input('email'),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Login failed',
            ], 401);
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
            $this->logger->error('Logout failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Logout failed',
            ], 500);
        }
    }

    /**
     * 忘记密码
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        try {
            $this->authService->sendPasswordResetLink($request->input('email'));

            return response()->json([
                'success' => true,
                'message' => 'Password reset link sent',
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            $this->logger->error('Password reset request failed', [
                'email' => $request->input('email'),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to send password reset link',
            ], 500);
        }
    }

    /**
     * 重置密码
     */
    public function resetPassword(Request $request): JsonResponse
    {
        try {
            $this->authService->resetPassword(
                $request->input('token'),
                $request->input('email'),
                $request->input('password')
            );

            return response()->json([
                'success' => true,
                'message' => 'Password reset successful',
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            $this->logger->error('Password reset failed', [
                'email' => $request->input('email'),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Password reset failed',
            ], 500);
        }
    }

    /**
     * 验证邮箱
     */
    public function verifyEmail(string $token): JsonResponse
    {
        try {
            $user = $this->authService->verifyEmail($token);

            return response()->json([
                'success' => true,
                'data' => $user,
                'message' => 'Email verified successfully',
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            $this->logger->error('Email verification failed', [
                'token' => $token,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Email verification failed',
            ], 500);
        }
    }

    /**
     * 刷新令牌
     */
    public function refresh(Request $request): JsonResponse
    {
        try {
            $result = $this->authService->refreshToken();

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Token refreshed successfully',
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Token refresh failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Token refresh failed',
            ], 401);
        }
    }

    /**
     * 获取当前用户信息
     */
    public function me(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            
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
            $this->logger->error('Failed to get current user', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to get user information',
            ], 500);
        }
    }
}
