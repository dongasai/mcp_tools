<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserAdminMenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 清空现有菜单
        DB::table('user_admin_menu')->truncate();

        // 创建用户后台菜单项
        $menus = [
            [
                'id' => 1,
                'parent_id' => 0,
                'order' => 1,
                'title' => '仪表板',
                'icon' => 'feather icon-bar-chart-2',
                'uri' => '/',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'parent_id' => 0,
                'order' => 2,
                'title' => '项目管理',
                'icon' => 'feather icon-folder',
                'uri' => 'projects',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 3,
                'parent_id' => 0,
                'order' => 3,
                'title' => '任务管理',
                'icon' => 'feather icon-check-square',
                'uri' => 'tasks',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 4,
                'parent_id' => 0,
                'order' => 4,
                'title' => 'Agent管理',
                'icon' => 'feather icon-cpu',
                'uri' => 'agents',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 10,
                'parent_id' => 0,
                'order' => 5,
                'title' => '问题管理',
                'icon' => 'feather icon-help-circle',
                'uri' => 'questions',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 5,
                'parent_id' => 0,
                'order' => 6,
                'title' => '个人设置',
                'icon' => 'feather icon-user',
                'uri' => 'profile',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 6,
                'parent_id' => 0,
                'order' => 7,
                'title' => 'GitHub集成',
                'icon' => 'feather icon-github',
                'uri' => 'github',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 7,
                'parent_id' => 0,
                'order' => 8,
                'title' => '开发工具',
                'icon' => 'feather icon-tool',
                'uri' => '#',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 8,
                'parent_id' => 7,
                'order' => 1,
                'title' => '用户管理',
                'icon' => 'feather icon-users',
                'uri' => 'users',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 9,
                'parent_id' => 7,
                'order' => 2,
                'title' => '任务评论',
                'icon' => 'feather icon-message-circle',
                'uri' => 'task-comments',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('user_admin_menu')->insert($menus);

        $this->command->info('用户后台菜单创建完成！');
    }
}
