<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('user_admin_menu', function (Blueprint $table) {
            // 添加缺失的字段
            if (!Schema::hasColumn('user_admin_menu', 'show')) {
                $table->tinyInteger('show')->default(1)->after('uri');
            }

            if (!Schema::hasColumn('user_admin_menu', 'extension')) {
                $table->string('extension', 50)->default('')->after('show');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_admin_menu', function (Blueprint $table) {
            $table->dropColumn(['show', 'extension']);
        });
    }
};
