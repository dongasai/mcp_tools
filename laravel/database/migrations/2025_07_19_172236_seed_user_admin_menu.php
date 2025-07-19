<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 清空现有菜单
        DB::table('user_admin_menu')->truncate();

        // 插入用户后台菜单
        $menus = [
            [
                'id' => 1,
                'parent_id' => 0,
                'order' => 1,
                'title' => '项目管理',
                'icon' => 'feather icon-folder',
                'uri' => '',
                'created_at' => now(),
                'updated_at' => now(),
                'show' => 1,
                'extension' => '',
            ],
            [
                'id' => 2,
                'parent_id' => 1,
                'order' => 1,
                'title' => '我的项目',
                'icon' => 'feather icon-list',
                'uri' => 'projects',
                'created_at' => now(),
                'updated_at' => now(),
                'show' => 1,
                'extension' => '',
            ],
            [
                'id' => 3,
                'parent_id' => 0,
                'order' => 2,
                'title' => '任务管理',
                'icon' => 'feather icon-check-square',
                'uri' => '',
                'created_at' => now(),
                'updated_at' => now(),
                'show' => 1,
                'extension' => '',
            ],
            [
                'id' => 4,
                'parent_id' => 3,
                'order' => 1,
                'title' => '我的任务',
                'icon' => 'feather icon-list',
                'uri' => 'tasks',
                'created_at' => now(),
                'updated_at' => now(),
                'show' => 1,
                'extension' => '',
            ],
            [
                'id' => 5,
                'parent_id' => 0,
                'order' => 3,
                'title' => 'Agent管理',
                'icon' => 'feather icon-cpu',
                'uri' => '',
                'created_at' => now(),
                'updated_at' => now(),
                'show' => 1,
                'extension' => '',
            ],
            [
                'id' => 6,
                'parent_id' => 5,
                'order' => 1,
                'title' => '我的Agent',
                'icon' => 'feather icon-server',
                'uri' => 'agents',
                'created_at' => now(),
                'updated_at' => now(),
                'show' => 1,
                'extension' => '',
            ],
            [
                'id' => 7,
                'parent_id' => 0,
                'order' => 4,
                'title' => '个人设置',
                'icon' => 'feather icon-user',
                'uri' => '',
                'created_at' => now(),
                'updated_at' => now(),
                'show' => 1,
                'extension' => '',
            ],
            [
                'id' => 8,
                'parent_id' => 7,
                'order' => 1,
                'title' => '个人资料',
                'icon' => 'feather icon-edit',
                'uri' => 'profile',
                'created_at' => now(),
                'updated_at' => now(),
                'show' => 1,
                'extension' => '',
            ],
            [
                'id' => 9,
                'parent_id' => 7,
                'order' => 2,
                'title' => 'GitHub集成',
                'icon' => 'feather icon-github',
                'uri' => 'github',
                'created_at' => now(),
                'updated_at' => now(),
                'show' => 1,
                'extension' => '',
            ],
        ];

        DB::table('user_admin_menu')->insert($menus);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('user_admin_menu')->truncate();
    }
};
