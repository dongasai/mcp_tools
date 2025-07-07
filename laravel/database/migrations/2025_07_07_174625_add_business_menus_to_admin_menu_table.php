<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 添加业务模块菜单项
        $menus = [
            [
                'id' => 1,
                'parent_id' => 0,
                'order' => 1,
                'title' => '系统管理',
                'icon' => 'feather icon-settings',
                'uri' => '',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'parent_id' => 1,
                'order' => 1,
                'title' => '用户管理',
                'icon' => 'feather icon-users',
                'uri' => 'users',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 3,
                'parent_id' => 0,
                'order' => 2,
                'title' => '项目管理',
                'icon' => 'feather icon-folder',
                'uri' => '',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 4,
                'parent_id' => 3,
                'order' => 1,
                'title' => '项目列表',
                'icon' => 'feather icon-list',
                'uri' => 'projects',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 5,
                'parent_id' => 3,
                'order' => 2,
                'title' => '任务管理',
                'icon' => 'feather icon-check-square',
                'uri' => 'tasks',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 6,
                'parent_id' => 0,
                'order' => 3,
                'title' => 'Agent管理',
                'icon' => 'feather icon-cpu',
                'uri' => '',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 7,
                'parent_id' => 6,
                'order' => 1,
                'title' => 'Agent列表',
                'icon' => 'feather icon-server',
                'uri' => 'agents',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($menus as $menu) {
            DB::table('admin_menu')->insert($menu);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 删除添加的菜单项
        DB::table('admin_menu')->whereIn('id', [1, 2, 3, 4, 5, 6, 7])->delete();
    }
};
