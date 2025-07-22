<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 由于问题类型已简化，将question_type字段设为可空并提供默认值
        Schema::table('agent_questions', function (Blueprint $table) {
            // 修改字段为可空，并设置默认值
            $table->enum('question_type', ['CHOICE', 'FEEDBACK'])->nullable()->default('FEEDBACK')->change();
        });

        // 更新现有数据，将所有NULL记录的question_type设为'FEEDBACK'
        DB::table('agent_questions')->whereNull('question_type')->update(['question_type' => 'FEEDBACK']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agent_questions', function (Blueprint $table) {
            // 恢复为NOT NULL
            $table->enum('question_type', ['CHOICE', 'FEEDBACK'])->nullable(false)->change();
        });
    }
};
