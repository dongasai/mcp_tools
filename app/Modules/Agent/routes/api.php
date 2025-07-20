<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Agent\Controllers\AgentTestController;
use App\Modules\Agent\Controllers\QuestionController;
use App\Modules\Agent\Controllers\QuestionTestController;
use App\Modules\Agent\Controllers\QuestionAnalyticsController;
use App\Modules\Agent\Controllers\QuestionPhase3TestController;

/*
|--------------------------------------------------------------------------
| Agent API Routes
|--------------------------------------------------------------------------
|
| Agent模块的测试路由定义（仅保留测试功能）
|
*/

Route::prefix('api/agents')->group(function () {
    // 测试路由（无需认证）
    Route::post('/test/create', [AgentTestController::class, 'quickCreate']);
    Route::get('/test/list', [AgentTestController::class, 'getAgents']);
    Route::get('/test/stats', [AgentTestController::class, 'getStats']);
    Route::get('/test/find/{agentId}', [AgentTestController::class, 'findByAgentId']);
    Route::post('/test/{id}/activate', [AgentTestController::class, 'testActivate']);
    Route::post('/test/{id}/deactivate', [AgentTestController::class, 'testDeactivate']);

    // Agent问题相关路由
    Route::get('/{agentId}/questions', [QuestionController::class, 'agentQuestions']);
});

// 问题测试路由
Route::prefix('api/questions/test')->group(function () {
    Route::post('/create', [QuestionTestController::class, 'testCreate']);
    Route::post('/create-multiple', [QuestionTestController::class, 'testCreateMultiple']);
    Route::post('/create-expiring', [QuestionTestController::class, 'testCreateExpiring']);
    Route::get('/list', [QuestionTestController::class, 'testList']);
    Route::get('/stats', [QuestionTestController::class, 'testStats']);
    Route::get('/high-priority', [QuestionTestController::class, 'testHighPriority']);
    Route::get('/expiring', [QuestionTestController::class, 'testExpiring']);
    Route::get('/sorting', [QuestionTestController::class, 'testSorting']);
    Route::post('/batch-operations', [QuestionTestController::class, 'testBatchOperations']);
    Route::post('/process-expired', [QuestionTestController::class, 'testProcessExpired']);
    Route::get('/agent/{agentId}', [QuestionTestController::class, 'testAgentQuestions']);
    Route::get('/agent/{agentId}/stats', [QuestionTestController::class, 'testAgentStats']);
    Route::get('/{questionId}', [QuestionTestController::class, 'testShow']);
    Route::post('/{questionId}/answer', [QuestionTestController::class, 'testAnswer']);
    Route::post('/{questionId}/ignore', [QuestionTestController::class, 'testIgnore']);
    Route::post('/{questionId}/notification', [QuestionTestController::class, 'testNotification']);
});

// 问题管理路由
Route::prefix('api/questions')->group(function () {
    Route::get('/', [QuestionController::class, 'index']);
    Route::post('/', [QuestionController::class, 'store']);
    Route::get('/stats', [QuestionController::class, 'stats']);
    Route::post('/process-expired', [QuestionController::class, 'processExpired']);
    Route::get('/{id}', [QuestionController::class, 'show']);
    Route::post('/{id}/answer', [QuestionController::class, 'answer']);
    Route::post('/{id}/ignore', [QuestionController::class, 'ignore']);
    Route::delete('/{id}', [QuestionController::class, 'destroy']);
});

// 用户问题路由
Route::prefix('api/users')->group(function () {
    Route::get('/{userId}/questions', [QuestionController::class, 'userQuestions']);
});

// 问题分析和批量操作路由
Route::prefix('api/questions')->group(function () {
    // 批量操作
    Route::post('/batch/update-status', [QuestionAnalyticsController::class, 'batchUpdateStatus']);
    Route::post('/batch/delete', [QuestionAnalyticsController::class, 'batchDelete']);

    // 搜索功能
    Route::post('/search', [QuestionAnalyticsController::class, 'search']);

    // 分析功能
    Route::get('/analytics/type-stats', [QuestionAnalyticsController::class, 'getTypeStats']);
    Route::get('/analytics/status-stats', [QuestionAnalyticsController::class, 'getStatusStats']);
    Route::get('/analytics/response-time', [QuestionAnalyticsController::class, 'getResponseTimeAnalysis']);
    Route::get('/analytics/agent-patterns', [QuestionAnalyticsController::class, 'getAgentPatterns']);
    Route::get('/analytics/trends', [QuestionAnalyticsController::class, 'getTrends']);
    Route::get('/analytics/comprehensive', [QuestionAnalyticsController::class, 'getComprehensiveReport']);

    // 上下文提取
    Route::get('/{questionId}/context', [QuestionAnalyticsController::class, 'extractContext']);
});

// Phase 3 高级功能测试路由
Route::prefix('api/questions/test/phase3')->group(function () {
    Route::post('/batch-update-status', [QuestionPhase3TestController::class, 'testBatchUpdateStatus']);
    Route::post('/batch-delete', [QuestionPhase3TestController::class, 'testBatchDelete']);
    Route::post('/search', [QuestionPhase3TestController::class, 'testSearch']);
    Route::post('/analytics', [QuestionPhase3TestController::class, 'testAnalytics']);
    Route::post('/context-extraction', [QuestionPhase3TestController::class, 'testContextExtraction']);
    Route::post('/run-all', [QuestionPhase3TestController::class, 'runAllTests']);
});
