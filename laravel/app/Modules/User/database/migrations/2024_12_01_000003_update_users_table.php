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
        Schema::table('users', function (Blueprint $table) {
            // 检查并添加缺失的列
            if (!Schema::hasColumn('users', 'avatar')) {
                $table->string('avatar')->nullable()->after('password');
            }

            if (!Schema::hasColumn('users', 'timezone')) {
                $table->string('timezone', 50)->default('UTC')->after('avatar');
            }

            if (!Schema::hasColumn('users', 'locale')) {
                $table->string('locale', 10)->default('en')->after('timezone');
            }

            if (!Schema::hasColumn('users', 'status')) {
                $table->enum('status', ['active', 'inactive', 'suspended', 'pending'])->default('pending')->after('locale');
            }

            if (!Schema::hasColumn('users', 'role')) {
                $table->enum('role', ['super_admin', 'admin', 'user'])->default('user')->after('status');
            }

            if (!Schema::hasColumn('users', 'settings')) {
                $table->json('settings')->nullable()->after('role');
            }

            if (!Schema::hasColumn('users', 'last_login_at')) {
                $table->timestamp('last_login_at')->nullable()->after('settings');
            }

            if (!Schema::hasColumn('users', 'last_login_ip')) {
                $table->string('last_login_ip', 45)->nullable()->after('last_login_at');
            }

            if (!Schema::hasColumn('users', 'deleted_at')) {
                $table->softDeletes()->after('updated_at');
            }
        });

        // 添加索引（简化版本，不检查是否存在）
        try {
            Schema::table('users', function (Blueprint $table) {
                $table->index(['status', 'created_at']);
                $table->index(['role', 'status']);
                $table->index('email_verified_at');
                $table->index('last_login_at');
            });
        } catch (\Exception $e) {
            // 索引可能已存在，忽略错误
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // 移除添加的列
            $table->dropColumn([
                'avatar',
                'timezone',
                'locale',
                'status',
                'role',
                'settings',
                'last_login_at',
                'last_login_ip',
                'deleted_at'
            ]);
        });
    }
};
