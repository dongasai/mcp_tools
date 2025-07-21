<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Hash;

class ResetUserPassword extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:reset-password
                            {identifier : 用户标识符（邮箱、用户名或ID）}
                            {password? : 新密码（如果不提供将生成随机密码）}
                            {--show-password : 显示新密码}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '重置用户密码';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $identifier = $this->argument('identifier');
        $password = $this->argument('password');
        $showPassword = $this->option('show-password');

        // 查找用户
        $user = $this->findUser($identifier);

        if (!$user) {
            $this->error("未找到用户: {$identifier}");
            return 1;
        }

        // 生成或使用提供的密码
        if (!$password) {
            $password = $this->generateRandomPassword();
            $this->info("生成随机密码: {$password}");
        }

        // 重置密码
        $user->password = Hash::make($password);
        $user->save();

        $this->info("用户密码重置成功！");
        $this->table(['字段', '值'], [
            ['ID', $user->id],
            ['姓名', $user->name],
            ['邮箱', $user->email],
            ['用户名', $user->username],
            ['状态', $user->status],
        ]);

        if ($showPassword || !$this->argument('password')) {
            $this->warn("新密码: {$password}");
        }

        return 0;
    }

    /**
     * 查找用户
     */
    private function findUser(string $identifier): ?User
    {
        // 尝试通过ID查找
        if (is_numeric($identifier)) {
            $user = User::find($identifier);
            if ($user) {
                return $user;
            }
        }

        // 尝试通过邮箱查找
        $user = User::where('email', $identifier)->first();
        if ($user) {
            return $user;
        }

        // 尝试通过用户名查找
        $user = User::where('username', $identifier)->first();
        if ($user) {
            return $user;
        }

        return null;
    }

    /**
     * 生成随机密码
     */
    private function generateRandomPassword(int $length = 12): string
    {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
        $password = '';

        for ($i = 0; $i < $length; $i++) {
            $password .= $characters[random_int(0, strlen($characters) - 1)];
        }

        return $password;
    }
}
