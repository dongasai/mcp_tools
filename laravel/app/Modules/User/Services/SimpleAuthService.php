<?php

namespace App\Modules\User\Services;

use App\Modules\User\Models\User;
use App\Modules\Core\Validators\SimpleValidator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * 简化的认证服务 - 用于调试
 */
class SimpleAuthService
{
    /**
     * 用户注册
     */
    public function register(array $data): array
    {
        // 验证数据
        $validatedData = SimpleValidator::validateUserRegistration($data);

        if (empty($validatedData)) {
            $validator = SimpleValidator::make($data, [
                'name' => 'required|string|min:2|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:8',
            ]);
            throw new \InvalidArgumentException('Validation failed: ' . $validator->getFirstError());
        }

        // 创建用户
        $user = User::create([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'password' => Hash::make($validatedData['password']),
            'role' => 'user',
            'status' => 'active',
            'settings' => [],
        ]);

        return [
            'user' => $user,
            'message' => 'Registration successful',
        ];
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
        if ($user->status !== 'active') {
            throw new \InvalidArgumentException('Account is not active');
        }

        // 执行登录
        Auth::login($user, $remember);

        // 更新最后登录信息
        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => request()->ip(),
        ]);

        return [
            'user' => $user,
            'message' => 'Login successful',
        ];
    }

    /**
     * 用户登出
     */
    public function logout(): void
    {
        Auth::logout();
    }

    /**
     * 获取当前用户
     */
    public function getCurrentUser(): ?User
    {
        return Auth::user();
    }
}
