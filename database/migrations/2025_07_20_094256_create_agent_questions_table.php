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
        Schema::create('agent_questions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('agent_id');
            $table->unsignedBigInteger('task_id')->nullable();
            $table->unsignedBigInteger('project_id')->nullable();
            $table->unsignedBigInteger('user_id');

            // 问题内容
            $table->string('title');
            $table->text('content');
            $table->json('context')->nullable();

            // 问题分类
            $table->enum('question_type', ['CHOICE', 'FEEDBACK']);
            $table->enum('priority', ['URGENT', 'HIGH', 'MEDIUM', 'LOW'])->default('MEDIUM');

            // 状态管理
            $table->enum('status', ['PENDING', 'ANSWERED', 'IGNORED'])->default('PENDING');

            // 回答相关
            $table->text('answer')->nullable();
            $table->enum('answer_type', ['TEXT', 'CHOICE', 'JSON', 'FILE'])->nullable();
            $table->json('answer_options')->nullable();
            $table->timestamp('answered_at')->nullable();
            $table->unsignedBigInteger('answered_by')->nullable();

            // 时间管理
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // 外键约束
            $table->foreign('agent_id')->references('id')->on('agents')->onDelete('cascade');
            $table->foreign('task_id')->references('id')->on('tasks')->onDelete('cascade');
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('answered_by')->references('id')->on('users')->onDelete('set null');

            // 索引
            $table->index('agent_id');
            $table->index('task_id');
            $table->index('project_id');
            $table->index('user_id');
            $table->index('status');
            $table->index('priority');
            $table->index('question_type');
            $table->index('created_at');
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agent_questions');
    }
};
