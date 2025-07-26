<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    /**
     * 演示 Seeder 执行顺序的示例
     */
    public function run(): void
    {
        $this->command->info('🚀 开始演示 Seeder 执行顺序...');
        $this->command->info('');
        
        $this->command->info('📋 步骤 1/4: 执行 Step1Seeder');
        $this->simulateSeeder('Step1Seeder', '创建基础数据');
        $this->command->info('✅ Step1Seeder 执行完成');
        $this->command->info('');
        
        $this->command->info('📋 步骤 2/4: 执行 Step2Seeder');
        $this->simulateSeeder('Step2Seeder', '创建配置数据');
        $this->command->info('✅ Step2Seeder 执行完成');
        $this->command->info('');
        
        $this->command->info('📋 步骤 3/4: 执行 Step3Seeder');
        $this->simulateSeeder('Step3Seeder', '创建业务数据');
        $this->command->info('✅ Step3Seeder 执行完成');
        $this->command->info('');
        
        $this->command->info('📋 步骤 4/4: 执行 Step4Seeder');
        $this->simulateSeeder('Step4Seeder', '创建关联数据');
        $this->command->info('✅ Step4Seeder 执行完成');
        $this->command->info('');
        
        $this->command->info('🎉 所有步骤按顺序执行完成！');
        $this->command->info('');
        $this->command->info('💡 关键点：');
        $this->command->info('   - 每个 $this->call() 都是同步执行');
        $this->command->info('   - 必须等待当前 Seeder 完全执行完毕才会执行下一个');
        $this->command->info('   - 如果任何一个失败，后续的都不会执行');
        $this->command->info('   - 这确保了严格的依赖顺序');
    }
    
    /**
     * 模拟 Seeder 执行过程
     */
    private function simulateSeeder(string $name, string $description): void
    {
        $this->command->info("   🔄 {$name} 开始执行: {$description}");
        
        // 模拟执行时间
        usleep(500000); // 0.5秒
        
        $this->command->info("   ✨ {$name} 数据创建完成");
    }
}
