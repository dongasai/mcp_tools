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
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('agent_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('project_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('parent_task_id')->nullable()->constrained('tasks')->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('type', ['main', 'sub', 'milestone', 'bug', 'feature', 'improvement'])->default('main');
            $table->enum('status', ['pending', 'in_progress', 'completed', 'blocked', 'cancelled', 'on_hold'])->default('pending');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->string('assigned_to')->nullable();
            $table->datetime('due_date')->nullable();
            $table->decimal('estimated_hours', 8, 2)->nullable();
            $table->decimal('actual_hours', 8, 2)->nullable();
            $table->integer('progress')->default(0);
            $table->json('tags')->nullable();
            $table->json('metadata')->nullable();
            $table->json('result')->nullable();
            $table->timestamps();

            // 索引
            $table->index(['user_id', 'status']);
            $table->index(['agent_id', 'status']);
            $table->index(['project_id', 'status']);
            $table->index(['parent_task_id']);
            $table->index('status');
            $table->index('type');
            $table->index('priority');
            $table->index('due_date');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
