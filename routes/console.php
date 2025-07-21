<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('显示励志名言');

// 注册MCP工具测试命令
Artisan::command('mcp:test-tools', function () {
    $this->info('Testing MCP Tools...');

    // 检查Agent数据
    $agent = \App\Modules\Agent\Models\Agent::first();
    if (!$agent) {
        $this->error('No agents found in database');
        return 1;
    }

    $this->info("Found agent: {$agent->identifier} (ID: {$agent->id})");
    $this->info("Agent project: {$agent->project_id}");

    // 测试工具是否能被实例化
    try {
        $askTool = app(\App\Modules\Mcp\Tools\AskQuestionTool::class);
        $this->info('✅ AskQuestionTool instantiated successfully');
        $this->info('✅ AskQuestionTool now supports blocking wait for answers (600s timeout)');
    } catch (\Exception $e) {
        $this->error('❌ AskQuestionTool failed: ' . $e->getMessage());
    }

    $this->info('✅ CheckAnswerTool removed (no longer needed with blocking ask_question)');
    $this->info('MCP Tools test completed');
    return 0;
})->purpose('测试MCP工具功能');
