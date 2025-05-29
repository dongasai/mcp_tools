<?php

namespace App\Modules\User\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Modules\User\Models\User;
use App\Modules\User\Services\UserService;
use App\Modules\Core\Contracts\LogInterface;

class UserController extends Controller
{
    protected UserService $userService;
    protected LogInterface $logger;

    public function __construct(UserService $userService, LogInterface $logger)
    {
        $this->userService = $userService;
        $this->logger = $logger;
    }

    /**
     * 获取用户列表
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = [
                'status' => $request->get('status'),
                'role' => $request->get('role'),
                'verified' => $request->get('verified'),
                'search' => $request->get('search'),
            ];

            $page = $request->get('page', 1);
            $limit = min($request->get('limit', 20), 100);

            $users = $this->userService->getUsers(array_filter($filters), $page, $limit);

            return response()->json([
                'success' => true,
                'data' => [
                    'users' => $users->items(),
                    'pagination' => [
                        'current_page' => $users->currentPage(),
                        'per_page' => $users->perPage(),
                        'total' => $users->total(),
                        'last_page' => $users->lastPage(),
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to get users', [
                'error' => $e->getMessage(),
                'filters' => $filters ?? [],
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve users',
            ], 500);
        }
    }

    /**
     * 创建用户
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $user = $this->userService->create($request->all());

            return response()->json([
                'success' => true,
                'data' => $user,
                'message' => 'User created successfully',
            ], 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            $this->logger->error('Failed to create user', [
                'error' => $e->getMessage(),
                'data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to create user',
            ], 500);
        }
    }

    /**
     * 获取单个用户
     */
    public function show(User $user): JsonResponse
    {
        try {
            $user->load(['projects', 'agents', 'gitHubConnection']);
            $stats = $user->getStats();

            return response()->json([
                'success' => true,
                'data' => [
                    'user' => $user,
                    'stats' => $stats,
                ],
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to get user', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve user',
            ], 500);
        }
    }

    /**
     * 更新用户
     */
    public function update(Request $request, User $user): JsonResponse
    {
        try {
            $updatedUser = $this->userService->update($user, $request->all());

            return response()->json([
                'success' => true,
                'data' => $updatedUser,
                'message' => 'User updated successfully',
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            $this->logger->error('Failed to update user', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to update user',
            ], 500);
        }
    }

    /**
     * 删除用户
     */
    public function destroy(User $user): JsonResponse
    {
        try {
            $this->userService->delete($user);

            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully',
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to delete user', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to delete user',
            ], 500);
        }
    }

    /**
     * 激活用户
     */
    public function activate(User $user): JsonResponse
    {
        try {
            $activatedUser = $this->userService->activate($user);

            return response()->json([
                'success' => true,
                'data' => $activatedUser,
                'message' => 'User activated successfully',
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to activate user', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to activate user',
            ], 500);
        }
    }

    /**
     * 停用用户
     */
    public function deactivate(User $user): JsonResponse
    {
        try {
            $deactivatedUser = $this->userService->deactivate($user);

            return response()->json([
                'success' => true,
                'data' => $deactivatedUser,
                'message' => 'User deactivated successfully',
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to deactivate user', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to deactivate user',
            ], 500);
        }
    }

    /**
     * 暂停用户
     */
    public function suspend(User $user): JsonResponse
    {
        try {
            $suspendedUser = $this->userService->suspend($user);

            return response()->json([
                'success' => true,
                'data' => $suspendedUser,
                'message' => 'User suspended successfully',
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to suspend user', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to suspend user',
            ], 500);
        }
    }

    /**
     * 验证用户邮箱
     */
    public function verifyEmail(User $user): JsonResponse
    {
        try {
            $verifiedUser = $this->userService->verifyEmail($user);

            return response()->json([
                'success' => true,
                'data' => $verifiedUser,
                'message' => 'Email verified successfully',
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to verify email', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to verify email',
            ], 500);
        }
    }

    /**
     * 更新用户设置
     */
    public function updateSettings(Request $request, User $user): JsonResponse
    {
        try {
            $updatedUser = $this->userService->updateSettings($user, $request->all());

            return response()->json([
                'success' => true,
                'data' => $updatedUser,
                'message' => 'Settings updated successfully',
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to update user settings', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'settings' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to update settings',
            ], 500);
        }
    }

    /**
     * 获取用户统计
     */
    public function statistics(): JsonResponse
    {
        try {
            $stats = $this->userService->getStatistics();

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to get user statistics', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve statistics',
            ], 500);
        }
    }
}
