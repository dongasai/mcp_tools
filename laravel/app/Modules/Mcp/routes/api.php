<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Mcp\Controllers\McpController;
use App\Modules\Mcp\Controllers\ResourceController;
use App\Modules\Mcp\Controllers\ToolController;

/*
|--------------------------------------------------------------------------
| MCP API Routes
|--------------------------------------------------------------------------
|
| Routes for Model Context Protocol endpoints
|
*/

Route::prefix('api/mcp')->middleware(['api'])->group(function () {
    
    // MCP Server Information
    Route::get('/info', [McpController::class, 'info']);
    Route::get('/capabilities', [McpController::class, 'capabilities']);
    Route::get('/status', [McpController::class, 'status']);

    // Authentication required routes
    Route::middleware(['mcp.auth'])->group(function () {
        
        // Resource endpoints
        Route::prefix('resources')->group(function () {
            Route::get('/', [ResourceController::class, 'list']);
            Route::get('/{resource}', [ResourceController::class, 'read']);
            Route::post('/{resource}', [ResourceController::class, 'create']);
            Route::put('/{resource}', [ResourceController::class, 'update']);
            Route::delete('/{resource}', [ResourceController::class, 'delete']);
        });

        // Tool endpoints
        Route::prefix('tools')->group(function () {
            Route::get('/', [ToolController::class, 'list']);
            Route::post('/{tool}/call', [ToolController::class, 'call']);
        });

        // Session management
        Route::post('/session/start', [McpController::class, 'startSession']);
        Route::post('/session/end', [McpController::class, 'endSession']);
        Route::get('/session/status', [McpController::class, 'sessionStatus']);
    });
});

/*
|--------------------------------------------------------------------------
| SSE (Server-Sent Events) Routes
|--------------------------------------------------------------------------
|
| Routes for real-time MCP communication using SSE
|
*/

Route::prefix('mcp/sse')->middleware(['mcp.auth'])->group(function () {
    Route::get('/events', [McpController::class, 'sseEvents']);
    Route::post('/send', [McpController::class, 'sseSend']);
});
