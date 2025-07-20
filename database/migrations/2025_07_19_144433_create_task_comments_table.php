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
        Schema::create('task_comments', function (Blueprint $table) {
            $table->id();

            // 关联字段
            $table->foreignId('task_id')->constrained('tasks')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('agent_id')->nullable()->constrained('agents')->onDelete('set null');
            $table->foreignId('parent_comment_id')->nullable()->constrained('task_comments')->onDelete('cascade');

            // 评论内容
            $table->text('content');
            $table->enum('comment_type', [
                'general',
                'status_update',
                'progress_report',
                'issue_report',
                'solution',
                'question',
                'answer',
                'system'
            ])->default('general');

            // 元数据和配置
            $table->json('metadata')->nullable();
            $table->boolean('is_internal')->default(false);
            $table->boolean('is_system')->default(false);
            $table->json('attachments')->nullable();

            // 时间戳
            $table->timestamp('edited_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            // 索引
            $table->index(['task_id', 'created_at']);
            $table->index(['user_id']);
            $table->index(['agent_id']);
            $table->index(['parent_comment_id']);
            $table->index(['comment_type']);
            $table->index(['is_internal']);
            $table->index(['is_system']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_comments');
    }
};
