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
        Schema::table('agents', function (Blueprint $table) {
            // 添加project_id外键字段
            $table->foreignId('project_id')->nullable()->after('user_id')->constrained()->onDelete('cascade');

            // 添加索引
            $table->index('project_id');
        });

        // 迁移现有数据：将allowed_projects中的第一个项目ID设置为project_id
        $this->migrateExistingData();

        Schema::table('agents', function (Blueprint $table) {
            // 移除allowed_projects字段（现在用project_id替代）
            $table->dropColumn('allowed_projects');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            // 恢复allowed_projects字段
            $table->json('allowed_projects')->nullable()->after('token_expires_at');
        });

        // 恢复数据：将project_id转换回allowed_projects数组
        $this->restoreExistingData();

        Schema::table('agents', function (Blueprint $table) {
            // 移除project_id字段
            $table->dropForeign(['project_id']);
            $table->dropColumn('project_id');
        });
    }

    /**
     * 迁移现有数据
     */
    private function migrateExistingData(): void
    {
        $agents = DB::table('agents')->whereNotNull('allowed_projects')->get();

        foreach ($agents as $agent) {
            $allowedProjects = json_decode($agent->allowed_projects, true);
            if (is_array($allowedProjects) && !empty($allowedProjects)) {
                // 使用第一个项目作为主项目
                $primaryProjectId = $allowedProjects[0];
                DB::table('agents')
                    ->where('id', $agent->id)
                    ->update(['project_id' => $primaryProjectId]);
            }
        }
    }

    /**
     * 恢复现有数据
     */
    private function restoreExistingData(): void
    {
        $agents = DB::table('agents')->whereNotNull('project_id')->get();

        foreach ($agents as $agent) {
            if ($agent->project_id) {
                // 将project_id转换为allowed_projects数组
                $allowedProjects = [$agent->project_id];
                DB::table('agents')
                    ->where('id', $agent->id)
                    ->update(['allowed_projects' => json_encode($allowedProjects)]);
            }
        }
    }
};
