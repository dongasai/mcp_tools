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
        Schema::create('agents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('agent_id')->unique(); // 唯一的Agent标识符
            $table->json('capabilities')->nullable(); // Agent能力列表
            $table->json('configuration')->nullable(); // Agent配置
            $table->enum('status', ['active', 'inactive', 'suspended', 'pending'])->default('pending');
            $table->timestamp('last_active_at')->nullable();
            $table->json('metadata')->nullable(); // 额外的元数据
            $table->timestamps();
            $table->softDeletes();

            // 索引
            $table->index(['user_id', 'status']);
            $table->index('agent_id');
            $table->index('status');
            $table->index('last_active_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agents');
    }
};
