<?php

namespace Modules\User\Services;

use Modules\User\Models\User;
use DLaravel\Contracts\LogInterface;
use DLaravel\Contracts\EventInterface;
use DLaravel\SimpleValidator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AuthService
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
     * 用户注册
     */
    public function register(array $data): array
    {
        // 检查注册是否启用
        if (!config('user.registration.enabled', true)) {
            throw new \InvalidArgumentException('Registration is currently disabled');
        }

        // 验证数据
        $validatedData = SimpleValidator::validateUserRegistration($data);

        if (empty($validatedData)) {
            $validator = SimpleValidator::make($data, [
                'name' => 'required|string|min:2|max:255',
                'email' => 'required|email',
                'password' => 'required|string|min:8',
            ]);
            throw new \InvalidArgumentException('Validation failed: ' . $validator->getFirstError());
        }

        // 检查邮箱域名限制
        if (!$this->isEmailDomainAllowed($validatedData['email'])) {
            throw new \InvalidArgumentException('Email domain is not allowed');
        }

        DB::beginTransaction();
        try {
            // 创建用户
            $userData = [
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'password' => $validatedData['password'],
                'role' => config('user.registration.default_role', 'user'),
                'status' => config('user.registration.auto_activate', false) ? 'active' : 'pending',
                'settings' => config('user.default_settings', []),
            ];

            $user = $this->userService->create($userData);

            // 生成邮箱验证令牌
            $verificationToken = null;
            if (config('user.registration.email_verification_required', true)) {
                $verificationToken = $this->generateEmailVerificationToken($user);
            }

            // 记录日志
            $this->logger->audit('user_registered', $user->id, [
                'email' => $user->email,
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            // 分发事件
            $this->eventDispatcher->dispatch(new \Modules\User\Events\UserRegistered($user, $verificationToken));

            DB::commit();

            return [
                'user' => $user,
                'verification_token' => $verificationToken,
                'requires_verification' => !is_null($verificationToken),
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * 用户登录
     */
    public function login(string $email, string $password, bool $remember = false): array
    {
        // 验证输入
        $validatedData = SimpleValidator::validateUserLogin([
            'email' => $email,
            'password' => $password,
        ]);

        if (empty($validatedData)) {
            throw new \InvalidArgumentException('Invalid email or password');
        }

        // 查找用户
        $user = User::where('email', $email)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            throw new \InvalidArgumentException('Invalid credentials');
        }

        // 检查用户状态
        if (!$user->isActive()) {
            throw new \InvalidArgumentException('Account is not active');
        }

        // 检查邮箱验证
        if (config('user.registration.email_verification_required', true) && !$user->hasVerifiedEmail()) {
            throw new \InvalidArgumentException('Email not verified');
        }

        // 执行登录
        Auth::login($user, $remember);

        // 更新最后登录信息
        $user->updateLastLogin(request()->ip());

        // 生成访问令牌（如果使用API认证）
        $token = $this->generateAccessToken($user);

        // 记录日志
        $this->logger->audit('user_logged_in', $user->id, [
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        // 分发事件
        $this->eventDispatcher->dispatch(new \Modules\User\Events\UserLoggedIn($user));

        return [
            'user' => $user,
            'token' => $token,
            'expires_in' => config('user.authentication.session_lifetime', 120) * 60,
        ];
    }

    /**
     * 用户登出
     */
    public function logout(): void
    {
        $user = Auth::user();

        if ($user) {
            // 记录日志
            $this->logger->audit('user_logged_out', $user->id, [
                'ip' => request()->ip(),
            ]);

            // 分发事件
            $this->eventDispatcher->dispatch(new \Modules\User\Events\UserLoggedOut($user));
        }

        Auth::logout();
    }

    /**
     * 发送密码重置链接
     */
    public function sendPasswordResetLink(string $email): void
    {
        $user = User::where('email', $email)->first();

        if (!$user) {
            throw new \InvalidArgumentException('User not found');
        }

        // 生成重置令牌
        $token = $this->generatePasswordResetToken($user);

        // 记录日志
        $this->logger->audit('password_reset_requested', $user->id, [
            'ip' => request()->ip(),
        ]);

        // 分发事件
        $this->eventDispatcher->dispatch(new \Modules\User\Events\PasswordResetRequested($user, $token));
    }

    /**
     * 重置密码
     */
    public function resetPassword(string $token, string $email, string $password): void
    {
        // 验证令牌
        if (!$this->validatePasswordResetToken($token, $email)) {
            throw new \InvalidArgumentException('Invalid or expired reset token');
        }

        $user = User::where('email', $email)->first();

        if (!$user) {
            throw new \InvalidArgumentException('User not found');
        }

        // 更新密码
        $this->userService->update($user, ['password' => $password]);

        // 清除重置令牌
        $this->clearPasswordResetToken($user);

        // 记录日志
        $this->logger->audit('password_reset_completed', $user->id, [
            'ip' => request()->ip(),
        ]);

        // 分发事件
        $this->eventDispatcher->dispatch(new \Modules\User\Events\PasswordReset($user));
    }

    /**
     * 验证邮箱
     */
    public function verifyEmail(string $token): User
    {
        $user = $this->validateEmailVerificationToken($token);

        if (!$user) {
            throw new \InvalidArgumentException('Invalid or expired verification token');
        }

        // 验证邮箱
        $this->userService->verifyEmail($user);

        // 清除验证令牌
        $this->clearEmailVerificationToken($user);

        return $user;
    }

    /**
     * 刷新访问令牌
     */
    public function refreshToken(): array
    {
        $user = Auth::user();

        if (!$user) {
            throw new \InvalidArgumentException('Unauthenticated');
        }

        $token = $this->generateAccessToken($user);

        return [
            'token' => $token,
            'expires_in' => config('user.authentication.session_lifetime', 120) * 60,
        ];
    }

    /**
     * 检查邮箱域名是否允许
     */
    protected function isEmailDomainAllowed(string $email): bool
    {
        $allowedDomains = config('user.registration.allowed_domains', '');

        if (empty($allowedDomains)) {
            return true;
        }

        $domain = substr(strrchr($email, '@'), 1);
        $allowedDomainsArray = array_map('trim', explode(',', $allowedDomains));

        return in_array($domain, $allowedDomainsArray);
    }

    /**
     * 生成访问令牌
     */
    protected function generateAccessToken(User $user): string
    {
        // 这里可以使用JWT或其他令牌机制
        // 简单实现：使用随机字符串
        return base64_encode($user->id . ':' . Str::random(40) . ':' . time());
    }

    /**
     * 生成邮箱验证令牌
     */
    protected function generateEmailVerificationToken(User $user): string
    {
        $token = Str::random(64);

        // 存储令牌（这里简化处理，实际应该存储到数据库）
        cache()->put("email_verification:{$user->id}", $token, now()->addHours(24));

        return $token;
    }

    /**
     * 生成密码重置令牌
     */
    protected function generatePasswordResetToken(User $user): string
    {
        $token = Str::random(64);

        // 存储令牌
        cache()->put("password_reset:{$user->email}", $token, now()->addHour());

        return $token;
    }

    /**
     * 验证邮箱验证令牌
     */
    protected function validateEmailVerificationToken(string $token): ?User
    {
        // 简化实现：从缓存中查找
        $cacheKeys = cache()->getStore()->getRedis()->keys('email_verification:*');

        foreach ($cacheKeys as $key) {
            if (cache()->get($key) === $token) {
                $userId = str_replace('email_verification:', '', $key);
                return User::find($userId);
            }
        }

        return null;
    }

    /**
     * 验证密码重置令牌
     */
    protected function validatePasswordResetToken(string $token, string $email): bool
    {
        $cachedToken = cache()->get("password_reset:{$email}");
        return $cachedToken === $token;
    }

    /**
     * 清除邮箱验证令牌
     */
    protected function clearEmailVerificationToken(User $user): void
    {
        cache()->forget("email_verification:{$user->id}");
    }

    /**
     * 清除密码重置令牌
     */
    protected function clearPasswordResetToken(User $user): void
    {
        cache()->forget("password_reset:{$user->email}");
    }
}
