<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 运行迁移
     */
    public function up(): void
    {
        Schema::create('agents', function (Blueprint $table) {
            $table->id();
            $table->string('agent_id')->unique(); // 唯一Agent标识符
            $table->string('name'); // 人类可读的名称
            $table->string('type')->nullable(); // Agent类型 (claude-3.5-sonnet, gpt-4, 等)
            $table->string('access_token', 500); // 认证访问令牌
            $table->json('permissions')->nullable(); // Agent权限
            $table->json('allowed_projects')->nullable(); // 此Agent可访问的项目ID
            $table->json('allowed_actions')->nullable(); // 此Agent可执行的操作
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            $table->timestamp('last_active_at')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->index('agent_id');
            $table->index(['user_id', 'status']);
            $table->index('status');
            $table->index('last_active_at');
        });
    }

    /**
     * 回滚迁移
     */
    public function down(): void
    {
        Schema::dropIfExists('agents');
    }
};
