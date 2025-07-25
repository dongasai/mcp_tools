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

// 注册Agent问题过期处理定时任务
use Illuminate\Support\Facades\Schedule;

// 每5分钟检查一次过期问题（自动处理已过期的问题）
Schedule::command('questions:process-expired')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/expired-questions.log'))
    ->when(function () {
        return config('agent.question_expiration.enabled', true);
    });

// 每30分钟发送即将过期的问题提醒
Schedule::command('questions:process-expired --notify-before=30')
    ->everyThirtyMinutes()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/question-expiry-reminders.log'))
    ->when(function () {
        return config('agent.question_expiration.enabled', true);
    });

// 每天早上9点发送1小时内过期的高优先级问题提醒
Schedule::command('questions:process-expired --notify-before=60')
    ->dailyAt('09:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/daily-question-reminders.log'))
    ->when(function () {
        return config('agent.question_expiration.enabled', true);
    });

// 任务自动流转调度
// 每小时执行一次任务自动流转（处理超时任务、父子任务关系）
Schedule::command('task:auto-flow')
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/task-auto-flow.log'))
    ->when(function () {
        return config('task.automation.enable_auto_flow', true);
    });

    