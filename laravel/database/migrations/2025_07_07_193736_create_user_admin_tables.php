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
        // 用户后台角色表
        Schema::create('user_admin_roles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();
            $table->string('slug', 50)->unique();
            $table->timestamps();
        });

        // 用户后台权限表
        Schema::create('user_admin_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);
            $table->string('slug', 50)->unique();
            $table->string('http_method')->nullable();
            $table->text('http_path')->nullable();
            $table->integer('order')->default(0);
            $table->bigInteger('parent_id')->default(0);
            $table->timestamps();
        });

        // 用户后台菜单表
        Schema::create('user_admin_menu', function (Blueprint $table) {
            $table->id();
            $table->integer('parent_id')->default(0);
            $table->integer('order')->default(0);
            $table->string('title', 50);
            $table->string('icon', 50)->nullable();
            $table->string('uri', 50)->nullable();
            $table->timestamps();
        });

        // 用户-角色关联表
        Schema::create('user_admin_role_users', function (Blueprint $table) {
            $table->integer('role_id');
            $table->integer('user_id');
            $table->timestamps();
            $table->unique(['role_id', 'user_id']);
        });

        // 角色-权限关联表
        Schema::create('user_admin_role_permissions', function (Blueprint $table) {
            $table->integer('role_id');
            $table->integer('permission_id');
            $table->timestamps();
            $table->unique(['role_id', 'permission_id']);
        });

        // 角色-菜单关联表
        Schema::create('user_admin_role_menu', function (Blueprint $table) {
            $table->integer('role_id');
            $table->integer('menu_id');
            $table->timestamps();
            $table->unique(['role_id', 'menu_id']);
        });

        // 权限-菜单关联表
        Schema::create('user_admin_permission_menu', function (Blueprint $table) {
            $table->integer('permission_id');
            $table->integer('menu_id');
            $table->timestamps();
            $table->unique(['permission_id', 'menu_id']);
        });

        // 用户后台设置表
        Schema::create('user_admin_settings', function (Blueprint $table) {
            $table->string('slug', 100)->primary();
            $table->longText('value');
            $table->timestamps();
        });

        // 用户后台扩展表
        Schema::create('user_admin_extensions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('version', 20)->default('');
            $table->tinyInteger('is_enabled')->default(0);
            $table->text('options')->nullable();
            $table->timestamps();
        });

        // 用户后台扩展历史表
        Schema::create('user_admin_extension_histories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->tinyInteger('type')->default(1);
            $table->string('version', 20)->default('');
            $table->text('detail')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_admin_extension_histories');
        Schema::dropIfExists('user_admin_extensions');
        Schema::dropIfExists('user_admin_settings');
        Schema::dropIfExists('user_admin_permission_menu');
        Schema::dropIfExists('user_admin_role_menu');
        Schema::dropIfExists('user_admin_role_permissions');
        Schema::dropIfExists('user_admin_role_users');
        Schema::dropIfExists('user_admin_menu');
        Schema::dropIfExists('user_admin_permissions');
        Schema::dropIfExists('user_admin_roles');
    }
};
