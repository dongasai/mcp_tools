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
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('status', ['pending', 'claimed', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->json('labels')->nullable(); // 任务标签/标记
            $table->timestamp('due_date')->nullable();
            $table->text('solution')->nullable(); // 完成时的解决方案描述
            $table->integer('time_spent')->nullable(); // 花费时间（分钟）
            $table->string('github_issue_url')->nullable(); // 关联的GitHub问题
            $table->integer('github_issue_number')->nullable();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->string('agent_id')->nullable(); // MCP Agent ID
            $table->timestamps();

            $table->index(['project_id', 'status']);
            $table->index(['assigned_to', 'status']);
            $table->index('agent_id');
            $table->index('priority');
            $table->index('due_date');
        });
    }

    /**
     * 回滚迁移
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
