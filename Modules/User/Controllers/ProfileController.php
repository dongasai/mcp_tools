<?php

namespace Modules\User\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\User\Services\ProfileService;
use DLaravel\Contracts\LogInterface;

class ProfileController extends Controller
{
    protected ProfileService $profileService;
    protected LogInterface $logger;

    public function __construct(ProfileService $profileService, LogInterface $logger)
    {
        $this->profileService = $profileService;
        $this->logger = $logger;
    }

    /**
     * 获取个人资料
     */
    public function show(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthenticated',
                ], 401);
            }

            $profile = $this->profileService->getProfile($user);

            return response()->json([
                'success' => true,
                'data' => $profile,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to get profile', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve profile',
            ], 500);
        }
    }

    /**
     * 更新个人资料
     */
    public function update(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthenticated',
                ], 401);
            }

            $updatedProfile = $this->profileService->updateProfile($user, $request->all());

            return response()->json([
                'success' => true,
                'data' => $updatedProfile,
                'message' => 'Profile updated successfully',
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            $this->logger->error('Failed to update profile', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to update profile',
            ], 500);
        }
    }

    /**
     * 上传头像
     */
    public function uploadAvatar(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthenticated',
                ], 401);
            }

            $avatarUrl = $this->profileService->uploadAvatar($user, $request->file('avatar'));

            return response()->json([
                'success' => true,
                'data' => ['avatar_url' => $avatarUrl],
                'message' => 'Avatar uploaded successfully',
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            $this->logger->error('Failed to upload avatar', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to upload avatar',
            ], 500);
        }
    }

    /**
     * 修改密码
     */
    public function changePassword(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthenticated',
                ], 401);
            }

            $this->profileService->changePassword(
                $user,
                $request->input('current_password'),
                $request->input('new_password')
            );

            return response()->json([
                'success' => true,
                'message' => 'Password changed successfully',
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            $this->logger->error('Failed to change password', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to change password',
            ], 500);
        }
    }

    /**
     * 更新设置
     */
    public function updateSettings(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthenticated',
                ], 401);
            }

            $updatedUser = $this->profileService->updateSettings($user, $request->all());

            return response()->json([
                'success' => true,
                'data' => $updatedUser,
                'message' => 'Settings updated successfully',
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to update settings', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'settings' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to update settings',
            ], 500);
        }
    }
}
