<?php

namespace Modules\User\Services;

use Modules\User\Models\User;
use DLaravel\Contracts\LogInterface;
use DLaravel\Contracts\EventInterface;
use DLaravel\SimpleValidator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

class ProfileService
{
    protected LogInterface $logger;
    protected EventInterface $eventDispatcher;
    protected UserService $userService;

    public function __construct(
        LogInterface $logger,
        EventInterface $eventDispatcher,
        UserService $userService
    ) {
        $this->logger = $logger;
        $this->eventDispatcher = $eventDispatcher;
        $this->userService = $userService;
    }

    /**
     * 获取用户资料
     */
    public function getProfile(User $user): array
    {
        $user->load(['projects', 'agents', 'gitHubConnection']);

        return [
            'user' => $user->makeHidden(['password', 'remember_token']),
            'stats' => $user->getStats(),
            'settings' => $user->settings ?: config('user.default_settings', []),
        ];
    }

    /**
     * 更新用户资料
     */
    public function updateProfile(User $user, array $data): User
    {
        // 验证数据
        $validatedData = SimpleValidator::validateUserProfile($data);

        if (empty($validatedData)) {
            $validator = SimpleValidator::make($data, [
                'name' => 'string|min:2|max:255',
                'timezone' => 'string',
                'locale' => 'string',
            ]);
            throw new \InvalidArgumentException('Validation failed: ' . $validator->getFirstError());
        }

        // 更新用户信息
        $updatedUser = $this->userService->update($user, $validatedData);

        // 记录日志
        $this->logger->audit('profile_updated', $user->id, [
            'updated_fields' => array_keys($validatedData),
        ]);

        return $updatedUser;
    }

    /**
     * 上传头像
     */
    public function uploadAvatar(User $user, ?UploadedFile $file): string
    {
        if (!$file) {
            throw new \InvalidArgumentException('No file uploaded');
        }

        // 验证文件
        $this->validateAvatarFile($file);

        // 删除旧头像
        if ($user->avatar) {
            Storage::disk(config('user.avatar.storage_disk', 'public'))->delete($user->avatar);
        }

        // 存储新头像
        $path = $file->store(
            config('user.avatar.storage_path', 'avatars'),
            config('user.avatar.storage_disk', 'public')
        );

        // 更新用户头像路径
        $this->userService->update($user, ['avatar' => $path]);

        // 记录日志
        $this->logger->audit('avatar_uploaded', $user->id, [
            'file_size' => $file->getSize(),
            'file_type' => $file->getMimeType(),
        ]);

        return Storage::url($path);
    }

    /**
     * 修改密码
     */
    public function changePassword(User $user, string $currentPassword, string $newPassword): void
    {
        // 验证当前密码
        if (!Hash::check($currentPassword, $user->password)) {
            throw new \InvalidArgumentException('Current password is incorrect');
        }

        // 验证新密码
        $this->validatePassword($newPassword);

        // 更新密码
        $this->userService->update($user, ['password' => $newPassword]);

        // 记录日志
        $this->logger->audit('password_changed', $user->id, [
            'ip' => request()->ip(),
        ]);

        // 分发事件
        $this->eventDispatcher->dispatch(new \Modules\User\Events\PasswordChanged($user));
    }

    /**
     * 更新用户设置
     */
    public function updateSettings(User $user, array $settings): User
    {
        // 验证设置
        $validatedSettings = $this->validateSettings($settings);

        // 更新设置
        $updatedUser = $this->userService->updateSettings($user, $validatedSettings);

        // 记录日志
        $this->logger->audit('settings_updated', $user->id, [
            'updated_keys' => array_keys($validatedSettings),
        ]);

        return $updatedUser;
    }

    /**
     * 验证头像文件
     */
    protected function validateAvatarFile(UploadedFile $file): void
    {
        $maxSize = config('user.avatar.max_size', 2048) * 1024; // 转换为字节
        $allowedTypes = config('user.avatar.allowed_types', ['jpg', 'jpeg', 'png', 'gif']);

        // 检查文件大小
        if ($file->getSize() > $maxSize) {
            throw new \InvalidArgumentException('File size exceeds maximum allowed size');
        }

        // 检查文件类型
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, $allowedTypes)) {
            throw new \InvalidArgumentException('File type not allowed');
        }

        // 检查MIME类型
        $mimeType = $file->getMimeType();
        $allowedMimeTypes = [
            'image/jpeg',
            'image/png',
            'image/gif',
        ];

        if (!in_array($mimeType, $allowedMimeTypes)) {
            throw new \InvalidArgumentException('Invalid file type');
        }
    }

    /**
     * 验证密码
     */
    protected function validatePassword(string $password): void
    {
        $minLength = config('user.password.min_length', 8);
        $requireUppercase = config('user.password.require_uppercase', true);
        $requireLowercase = config('user.password.require_lowercase', true);
        $requireNumbers = config('user.password.require_numbers', true);
        $requireSymbols = config('user.password.require_symbols', false);

        if (strlen($password) < $minLength) {
            throw new \InvalidArgumentException("Password must be at least {$minLength} characters long");
        }

        if ($requireUppercase && !preg_match('/[A-Z]/', $password)) {
            throw new \InvalidArgumentException('Password must contain at least one uppercase letter');
        }

        if ($requireLowercase && !preg_match('/[a-z]/', $password)) {
            throw new \InvalidArgumentException('Password must contain at least one lowercase letter');
        }

        if ($requireNumbers && !preg_match('/[0-9]/', $password)) {
            throw new \InvalidArgumentException('Password must contain at least one number');
        }

        if ($requireSymbols && !preg_match('/[^A-Za-z0-9]/', $password)) {
            throw new \InvalidArgumentException('Password must contain at least one symbol');
        }
    }

    /**
     * 验证用户设置
     */
    protected function validateSettings(array $settings): array
    {
        $validatedSettings = [];

        // 验证主题设置
        if (isset($settings['theme'])) {
            $allowedThemes = ['light', 'dark', 'auto'];
            if (in_array($settings['theme'], $allowedThemes)) {
                $validatedSettings['theme'] = $settings['theme'];
            }
        }

        // 验证语言设置
        if (isset($settings['language'])) {
            $allowedLanguages = ['en', 'zh-CN', 'zh-TW', 'ja', 'ko'];
            if (in_array($settings['language'], $allowedLanguages)) {
                $validatedSettings['language'] = $settings['language'];
            }
        }

        // 验证时区设置
        if (isset($settings['timezone'])) {
            $allowedTimezones = timezone_identifiers_list();
            if (in_array($settings['timezone'], $allowedTimezones)) {
                $validatedSettings['timezone'] = $settings['timezone'];
            }
        }

        // 验证通知设置
        if (isset($settings['notifications']) && is_array($settings['notifications'])) {
            $validatedSettings['notifications'] = [];
            $allowedNotificationKeys = [
                'email', 'browser', 'task_assigned', 'task_completed',
                'project_updates', 'agent_notifications'
            ];

            foreach ($settings['notifications'] as $key => $value) {
                if (in_array($key, $allowedNotificationKeys) && is_bool($value)) {
                    $validatedSettings['notifications'][$key] = $value;
                }
            }
        }

        // 验证隐私设置
        if (isset($settings['privacy']) && is_array($settings['privacy'])) {
            $validatedSettings['privacy'] = [];
            $allowedPrivacyKeys = ['profile_public', 'show_email', 'show_last_login'];

            foreach ($settings['privacy'] as $key => $value) {
                if (in_array($key, $allowedPrivacyKeys) && is_bool($value)) {
                    $validatedSettings['privacy'][$key] = $value;
                }
            }
        }

        return $validatedSettings;
    }
}
