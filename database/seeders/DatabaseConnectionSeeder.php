<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\Dbcont\Models\DatabaseConnection;
use App\Modules\Agent\Models\Agent;

class DatabaseConnectionSeeder extends Seeder
{
    public function run()
    {
        // 防止重复执行
        if (DatabaseConnection::where('name', '默认SQLite数据库')->exists()) {
            return;
        }

        // 创建默认SQLite数据库连接
        $db = DatabaseConnection::create([
            'name' => '默认SQLite数据库',
            'driver' => 'sqlite',
            'database' => database_path('database.sqlite'),
            'is_default' => true
        ]);

        // 获取第一个Agent并授予全部权限
        if ($agent = Agent::first()) {
            $db->agents()->attach($agent->id, [
                'can_select' => true,
                'can_insert' => true,
                'can_update' => true,
                'can_delete' => true,
                'can_execute' => true
            ]);
        }
    }
}