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
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('timezone')->default('UTC');
            $table->enum('status', ['active', 'inactive', 'archived'])->default('active');
            $table->json('repositories')->nullable(); // GitHub仓库
            $table->json('settings')->nullable(); // 项目设置
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('name');
        });
    }

    /**
     * 回滚迁移
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
