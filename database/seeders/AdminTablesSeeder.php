<?php

namespace Database\Seeders;

use Dcat\Admin\Models\Administrator;
use Dcat\Admin\Models\Menu;
use Dcat\Admin\Models\Permission;
use Dcat\Admin\Models\Role;
use Illuminate\Database\Seeder;

class AdminTablesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $createdAt = date('Y-m-d H:i:s');

        // create a user.
        Administrator::truncate();
        Administrator::create([
            'username'   => 'admin',
            'password'   => bcrypt('admin'),
            'name'       => 'Administrator',
            'created_at' => $createdAt,
        ]);

        // create a role.
        Role::truncate();
        Role::create([
            'name'       => 'Administrator',
            'slug'       => Role::ADMINISTRATOR,
            'created_at' => $createdAt,
        ]);

        // add role to user.
        $admin = Administrator::where('username', 'admin')->first();
        $adminRole = Role::where('slug', Role::ADMINISTRATOR)->first();
        if ($admin && $adminRole) {
            $admin->roles()->save($adminRole);
        }

        //create a permission
        Permission::truncate();
        Permission::insert([
            [
                'id'          => 1,
                'name'        => 'Auth management',
                'slug'        => 'auth-management',
                'http_method' => '',
                'http_path'   => '',
                'parent_id'   => 0,
                'order'       => 1,
                'created_at'  => $createdAt,
            ],
            [
                'id'          => 2,
                'name'        => 'Users',
                'slug'        => 'users',
                'http_method' => '',
                'http_path'   => '/auth/users*',
                'parent_id'   => 1,
                'order'       => 2,
                'created_at'  => $createdAt,
            ],
            [
                'id'          => 3,
                'name'        => 'Roles',
                'slug'        => 'roles',
                'http_method' => '',
                'http_path'   => '/auth/roles*',
                'parent_id'   => 1,
                'order'       => 3,
                'created_at'  => $createdAt,
            ],
            [
                'id'          => 4,
                'name'        => 'Permissions',
                'slug'        => 'permissions',
                'http_method' => '',
                'http_path'   => '/auth/permissions*',
                'parent_id'   => 1,
                'order'       => 4,
                'created_at'  => $createdAt,
            ],
            [
                'id'          => 5,
                'name'        => 'Menu',
                'slug'        => 'menu',
                'http_method' => '',
                'http_path'   => '/auth/menu*',
                'parent_id'   => 1,
                'order'       => 5,
                'created_at'  => $createdAt,
            ],
            [
                'id'          => 6,
                'name'        => 'Extension',
                'slug'        => 'extension',
                'http_method' => '',
                'http_path'   => '/auth/extensions*',
                'parent_id'   => 1,
                'order'       => 6,
                'created_at'  => $createdAt,
            ],
        ]);

        // add default menus.
        Menu::truncate();
        Menu::insert([
            // 工作台
            [
                'id'            => 1,
                'parent_id'     => 0,
                'order'         => 1,
                'title'         => '工作台',
                'icon'          => 'feather icon-bar-chart-2',
                'uri'           => '/',
                'created_at'    => $createdAt,
                'updated_at'    => $createdAt,
                'show'          => 1,
                'extension'     => '',
            ],
            // 系统管理
            [
                'id'            => 2,
                'parent_id'     => 0,
                'order'         => 2,
                'title'         => '系统管理',
                'icon'          => 'feather icon-settings',
                'uri'           => '',
                'created_at'    => $createdAt,
                'updated_at'    => $createdAt,
                'show'          => 1,
                'extension'     => '',
            ],
            [
                'id'            => 3,
                'parent_id'     => 2,
                'order'         => 3,
                'title'         => '管理员',
                'icon'          => '',
                'uri'           => 'auth/users',
                'created_at'    => $createdAt,
                'updated_at'    => $createdAt,
                'show'          => 1,
                'extension'     => '',
            ],
            [
                'id'            => 4,
                'parent_id'     => 2,
                'order'         => 4,
                'title'         => '角色',
                'icon'          => '',
                'uri'           => 'auth/roles',
                'created_at'    => $createdAt,
                'updated_at'    => $createdAt,
                'show'          => 1,
                'extension'     => '',
            ],
            [
                'id'            => 5,
                'parent_id'     => 2,
                'order'         => 5,
                'title'         => '权限',
                'icon'          => '',
                'uri'           => 'auth/permissions',
                'created_at'    => $createdAt,
                'updated_at'    => $createdAt,
                'show'          => 1,
                'extension'     => '',
            ],
            [
                'id'            => 6,
                'parent_id'     => 2,
                'order'         => 6,
                'title'         => '菜单',
                'icon'          => '',
                'uri'           => 'auth/menu',
                'created_at'    => $createdAt,
                'updated_at'    => $createdAt,
                'show'          => 1,
                'extension'     => '',
            ],
            // 项目管理
            [
                'id'            => 7,
                'parent_id'     => 0,
                'order'         => 7,
                'title'         => '项目管理',
                'icon'          => 'feather icon-folder',
                'uri'           => '',
                'created_at'    => $createdAt,
                'updated_at'    => $createdAt,
                'show'          => 1,
                'extension'     => '',
            ],
            [
                'id'            => 8,
                'parent_id'     => 7,
                'order'         => 8,
                'title'         => '用户管理',
                'icon'          => '',
                'uri'           => 'users',
                'created_at'    => $createdAt,
                'updated_at'    => $createdAt,
                'show'          => 1,
                'extension'     => '',
            ],
            [
                'id'            => 9,
                'parent_id'     => 7,
                'order'         => 9,
                'title'         => '项目列表',
                'icon'          => '',
                'uri'           => 'projects',
                'created_at'    => $createdAt,
                'updated_at'    => $createdAt,
                'show'          => 1,
                'extension'     => '',
            ],
            [
                'id'            => 10,
                'parent_id'     => 7,
                'order'         => 10,
                'title'         => '任务管理',
                'icon'          => '',
                'uri'           => 'tasks',
                'created_at'    => $createdAt,
                'updated_at'    => $createdAt,
                'show'          => 1,
                'extension'     => '',
            ],
            // Agent管理
            [
                'id'            => 11,
                'parent_id'     => 0,
                'order'         => 11,
                'title'         => 'Agent管理',
                'icon'          => 'feather icon-cpu',
                'uri'           => '',
                'created_at'    => $createdAt,
                'updated_at'    => $createdAt,
                'show'          => 1,
                'extension'     => '',
            ],
            [
                'id'            => 12,
                'parent_id'     => 11,
                'order'         => 12,
                'title'         => 'Agent列表',
                'icon'          => '',
                'uri'           => 'agents',
                'created_at'    => $createdAt,
                'updated_at'    => $createdAt,
                'show'          => 1,
                'extension'     => '',
            ],
            [
                'id'            => 15,
                'parent_id'     => 11,
                'order'         => 13,
                'title'         => '问题管理',
                'icon'          => '',
                'uri'           => 'questions',
                'created_at'    => $createdAt,
                'updated_at'    => $createdAt,
                'show'          => 1,
                'extension'     => '',
            ],
            // 开发工具
            [
                'id'            => 13,
                'parent_id'     => 0,
                'order'         => 13,
                'title'         => '开发工具',
                'icon'          => 'feather icon-tool',
                'uri'           => '',
                'created_at'    => $createdAt,
                'updated_at'    => $createdAt,
                'show'          => 1,
                'extension'     => '',
            ],
            [
                'id'            => 14,
                'parent_id'     => 13,
                'order'         => 14,
                'title'         => '扩展',
                'icon'          => '',
                'uri'           => 'auth/extensions',
                'created_at'    => $createdAt,
                'updated_at'    => $createdAt,
                'show'          => 1,
                'extension'     => '',
            ],
        ]);

        (new Menu())->flushCache();
    }
}
