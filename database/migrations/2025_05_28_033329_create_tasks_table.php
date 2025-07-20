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
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            // 临时移除 agent_id 外键约束，将在后续迁移中添加
            $table->unsignedBigInteger('agent_id')->nullable();
            $table->foreignId('project_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('parent_task_id')->nullable()->constrained('tasks')->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('type', ['main', 'sub', 'milestone', 'bug', 'feature', 'improvement'])->default('main');
            $table->enum('status', ['pending', 'in_progress', 'completed', 'blocked', 'cancelled', 'on_hold'])->default('pending');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('due_date')->nullable();
            $table->decimal('estimated_hours', 8, 2)->nullable();
            $table->decimal('actual_hours', 8, 2)->nullable();
            $table->integer('progress')->default(0);
            $table->json('tags')->nullable();
            $table->json('metadata')->nullable();
            $table->json('result')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'status']);
            $table->index(['assigned_to', 'status']);
            $table->index(['user_id', 'status']);
            $table->index('agent_id');
            $table->index('priority');
            $table->index('due_date');
            $table->index('type');
            $table->index('parent_task_id');
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
