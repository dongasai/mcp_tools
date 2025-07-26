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
        Schema::create('database_connections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id')->comment('项目ID');
            $table->string('name')->comment('连接名称');
            $table->enum('type', ['SQLITE', 'MYSQL', 'MARIADB'])->comment('数据库类型');
            $table->string('host')->nullable()->comment('主机地址');
            $table->unsignedInteger('port')->nullable()->comment('端口号');
            $table->string('database')->comment('数据库名');
            $table->string('username')->nullable()->comment('用户名');
            $table->text('password')->nullable()->comment('密码(加密存储)');
            $table->json('options')->nullable()->comment('连接选项');
            $table->enum('status', ['ACTIVE', 'INACTIVE', 'ERROR'])->default('ACTIVE')->comment('连接状态');
            $table->timestamp('last_tested_at')->nullable()->comment('最后测试时间');
            $table->timestamps();
            
            $table->index('project_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('database_connections');
    }
};