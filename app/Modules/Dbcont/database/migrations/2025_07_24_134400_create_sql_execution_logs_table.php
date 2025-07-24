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
        Schema::create('sql_execution_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('agent_id')->comment('Agent ID');
            $table->unsignedBigInteger('database_connection_id')->comment('数据库连接ID');
            $table->text('sql_statement')->comment('SQL语句');
            $table->unsignedInteger('execution_time')->comment('执行时间(毫秒)');
            $table->unsignedInteger('rows_affected')->default(0)->comment('影响行数');
            $table->unsignedBigInteger('result_size')->default(0)->comment('结果大小(字节)');
            $table->enum('status', ['SUCCESS', 'ERROR', 'TIMEOUT'])->comment('执行状态');
            $table->text('error_message')->nullable()->comment('错误信息');
            $table->ipAddress('ip_address')->comment('客户端IP');
            $table->timestamp('executed_at')->comment('执行时间');
            $table->timestamps();
            
            $table->index('agent_id');
            $table->index('database_connection_id');
            $table->index('status');
            $table->index('executed_at');
            $table->foreign('database_connection_id')->references('id')->on('database_connections')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sql_execution_logs');
    }
};