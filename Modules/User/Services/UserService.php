<?php

namespace Modules\User\Services;

use Modules\User\Models\User;
use App\Modules\Core\Contracts\LogInterface;
use App\Modules\Core\Contracts\EventInterface;
use App\Modules\Core\Contracts\ValidationInterface;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

class UserService
{
    protected LogInterface $logger;
    protected EventInterface $eventDispatcher;
    protected ValidationInterface $validator;

    public function __construct(
        LogInterface $logger,
        EventInterface $eventDispatcher,
        ValidationInterface $validator
    ) {
        $this->logger = $logger;
        $this->eventDispatcher = $eventDispatcher;
        $this->validator = $validator;
    }

    /**
     * 创建用户
     */
    public function create(array $data): User
    {
        // 验证数据
        $validatedData = $this->validator->validate($data, [
            'name' => 'required|string|min:2|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => 'string|in:super_admin,admin,user',
            'timezone' => 'string',
            'locale' => 'string',
        ]);

        if (empty($validatedData)) {
            throw new \InvalidArgumentException('验证失败: ' . implode(', ', $this->validator->getErrors()));
        }

        DB::beginTransaction();
        try {
            // 设置默认值
            $userData = array_merge([
                'role' => User::ROLE_USER,
                'status' => User::STATUS_PENDING,
                'timezone' => config('app.timezone'),
                'locale' => config('app.locale'),
                'settings' => [],
            ], $validatedData);

            // 加密密码
            $userData['password'] = Hash::make($userData['password']);

            $user = User::create($userData);

            // 记录日志
            $this->logger->audit('user_created', 'system', [
                'user_id' => $user->id,
                'email' => $user->email,
                'role' => $user->role,
            ]);

            // 分发事件
            $this->eventDispatcher->dispatch(new \Modules\User\Events\UserCreated($user));

            DB::commit();
            return $user;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->logger->error('创建用户失败', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);
            throw $e;
        }
    }

    /**
     * 更新用户
     */
    public function update(User $user, array $data): User
    {
        // 验证数据
        $rules = [
            'name' => 'string|min:2|max:255',
            'email' => 'email|unique:users,email,' . $user->id,
            'password' => 'string|min:8',
            'role' => 'string|in:super_admin,admin,user',
            'status' => 'string|in:active,inactive,suspended,pending',
            'timezone' => 'string',
            'locale' => 'string',
            'avatar' => 'string',
        ];

        $validatedData = $this->validator->validate($data, $rules);

        if (empty($validatedData)) {
            throw new \InvalidArgumentException('验证失败: ' . implode(', ', $this->validator->getErrors()));
        }

        DB::beginTransaction();
        try {
            $oldData = $user->toArray();

            // 如果更新密码，需要加密
            if (isset($validatedData['password'])) {
                $validatedData['password'] = Hash::make($validatedData['password']);
            }

            $user->update($validatedData);

            // 记录日志
            $this->logger->audit('user_updated', auth()->user()?->id ?? 'system', [
                'user_id' => $user->id,
                'changes' => array_keys($validatedData),
                'old_email' => $oldData['email'],
                'new_email' => $user->email,
            ]);

            // 分发事件
            $this->eventDispatcher->dispatch(new \Modules\User\Events\UserUpdated($user, $oldData));

            DB::commit();
            return $user->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->logger->error('更新用户失败', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'data' => $data,
            ]);
            throw $e;
        }
    }

    /**
     * 删除用户（软删除）
     */
    public function delete(User $user): bool
    {
        DB::beginTransaction();
        try {
            $user->delete();

            // 记录日志
            $this->logger->audit('user_deleted', auth()->user()?->id ?? 'system', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            // 分发事件
            $this->eventDispatcher->dispatch(new \Modules\User\Events\UserDeleted($user));

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->logger->error('删除用户失败', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * 激活用户
     */
    public function activate(User $user): User
    {
        return $this->updateStatus($user, User::STATUS_ACTIVE);
    }

    /**
     * 停用用户
     */
    public function deactivate(User $user): User
    {
        return $this->updateStatus($user, User::STATUS_INACTIVE);
    }

    /**
     * 暂停用户
     */
    public function suspend(User $user): User
    {
        return $this->updateStatus($user, User::STATUS_SUSPENDED);
    }

    /**
     * 更新用户状态
     */
    protected function updateStatus(User $user, string $status): User
    {
        $oldStatus = $user->status;
        $user->update(['status' => $status]);

        // 记录日志
        $this->logger->audit('user_status_changed', auth()->user()?->id ?? 'system', [
            'user_id' => $user->id,
            'old_status' => $oldStatus,
            'new_status' => $status,
        ]);

        // 分发事件
        $this->eventDispatcher->dispatch(new \Modules\User\Events\UserStatusChanged($user, $oldStatus, $status));

        return $user->fresh();
    }

    /**
     * 验证用户邮箱
     */
    public function verifyEmail(User $user): User
    {
        $user->update(['email_verified_at' => now()]);

        // 记录日志
        $this->logger->audit('user_email_verified', $user->id, [
            'email' => $user->email,
        ]);

        // 分发事件
        $this->eventDispatcher->dispatch(new \Modules\User\Events\UserEmailVerified($user));

        return $user->fresh();
    }

    /**
     * 更新用户设置
     */
    public function updateSettings(User $user, array $settings): User
    {
        $currentSettings = $user->settings ?: [];
        $newSettings = array_merge($currentSettings, $settings);

        $user->update(['settings' => $newSettings]);

        // 记录日志
        $this->logger->audit('user_settings_updated', $user->id, [
            'updated_keys' => array_keys($settings),
        ]);

        return $user->fresh();
    }

    /**
     * 获取用户列表
     */
    public function getUsers(array $filters = [], int $page = 1, int $limit = 20): LengthAwarePaginator
    {
        $query = User::query();

        // 应用筛选条件
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['role'])) {
            $query->where('role', $filters['role']);
        }

        if (isset($filters['verified'])) {
            if ($filters['verified']) {
                $query->whereNotNull('email_verified_at');
            } else {
                $query->whereNull('email_verified_at');
            }
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('created_at', 'desc')
                    ->paginate($limit, ['*'], 'page', $page);
    }

    /**
     * 根据邮箱查找用户
     */
    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    /**
     * 获取用户统计
     */
    public function getStatistics(): array
    {
        return [
            'total_users' => User::count(),
            'active_users' => User::where('status', User::STATUS_ACTIVE)->count(),
            'verified_users' => User::whereNotNull('email_verified_at')->count(),
            'admin_users' => User::whereIn('role', [User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN])->count(),
            'new_users_this_month' => User::where('created_at', '>=', now()->startOfMonth())->count(),
        ];
    }
}
