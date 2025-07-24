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
        Schema::create('agent_database_permissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('agent_id')->comment('Agent ID');
            $table->unsignedBigInteger('database_connection_id')->comment('数据库连接ID');
            $table->enum('permission_level', ['READ_ONLY', 'READ_WRITE', 'ADMIN'])->comment('权限级别');
            $table->json('allowed_tables')->nullable()->comment('允许访问的表');
            $table->json('denied_operations')->nullable()->comment('禁止的操作');
            $table->unsignedInteger('max_query_time')->default(30)->comment('最大查询时间(秒)');
            $table->unsignedInteger('max_result_rows')->default(1000)->comment('最大结果行数');
            $table->timestamps();
            
            $table->unique(['agent_id', 'database_connection_id']);
            $table->index('agent_id');
            $table->index('database_connection_id');
            $table->foreign('database_connection_id')->references('id')->on('database_connections')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agent_database_permissions');
    }
};