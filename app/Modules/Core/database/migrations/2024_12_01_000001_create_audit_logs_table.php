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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('action')->index();
            $table->string('user')->index();
            $table->json('data')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('module', 50)->default('core')->index();
            $table->string('level', 20)->default('info')->index();
            $table->timestamps();

            // 索引优化
            $table->index(['action', 'created_at']);
            $table->index(['user', 'created_at']);
            $table->index(['module', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
