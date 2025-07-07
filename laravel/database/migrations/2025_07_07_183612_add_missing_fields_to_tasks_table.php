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
        // 由于SQLite不支持复杂的ALTER TABLE操作，我们需要重新创建表
        Schema::dropIfExists('tasks');

        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('agent_id')->nullable()->constrained()->onDelete('set null');
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
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 恢复原来的表结构
        Schema::dropIfExists('tasks');

        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('status', ['pending', 'claimed', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->json('labels')->nullable();
            $table->timestamp('due_date')->nullable();
            $table->text('solution')->nullable();
            $table->integer('time_spent')->nullable();
            $table->string('github_issue_url')->nullable();
            $table->integer('github_issue_number')->nullable();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->string('agent_id')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'status']);
            $table->index(['assigned_to', 'status']);
            $table->index('agent_id');
            $table->index('priority');
            $table->index('due_date');
        });
    }
};
