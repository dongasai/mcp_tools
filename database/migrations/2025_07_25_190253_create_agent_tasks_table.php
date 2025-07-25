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
        Schema::create('agent_tasks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('agent_id');
            $table->unsignedBigInteger('main_task_id')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status')->default('pending');
            $table->string('type');
            $table->string('priority')->default('medium');
            $table->json('execution_data')->nullable();
            $table->json('result_data')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('estimated_duration')->nullable();
            $table->integer('actual_duration')->nullable();
            $table->integer('retry_count')->default(0);
            $table->integer('max_retries')->default(3);
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            // 索引
            $table->index(['agent_id']);
            $table->index(['main_task_id']);
            $table->index(['status']);
            $table->index(['type']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agent_tasks');
    }
};
