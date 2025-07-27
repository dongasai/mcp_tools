<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // 注册用户后台资源归属验证中间件
        $middleware->alias([
            'user-admin.resource-ownership' => \App\UserAdmin\Middleware\EnsureResourceOwnership::class,
            // Agent认证中间件
            'agent.auth' => \Modules\MCP\Middleware\AgentAuthMiddleware::class,
            'agent.project' => \Modules\MCP\Middleware\ProjectAccessMiddleware::class,
            // MCP认证中间件
            'mcp.auth' => \Modules\MCP\Middleware\AgentAuthMiddleware::class,
            // MCP中间件（用于php-mcp/laravel包）
            'mcp' => \Modules\MCP\Middleware\AgentAuthMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
