<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class IndependentSeeder extends Seeder
{
    /**
     * 这个 Seeder 不在 DatabaseSeeder 中被调用
     * 用于验证 --seed 参数是否会自动执行所有 Seeder
     */
    public function run(): void
    {
        $this->command->info('🔥 IndependentSeeder 被执行了！');
        $this->command->info('   这证明了 --seed 参数会执行所有 Seeder 文件');
    }
}
