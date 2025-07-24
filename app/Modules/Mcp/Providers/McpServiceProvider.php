<?php

namespace App\Modules\Mcp\Providers;

use Illuminate\Support\ServiceProvider;
use App\Modules\Mcp\Services\McpService;

class McpServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // 注册MCP服务
        $this->app->singleton(McpService::class);

        // 注意：MCP配置使用项目根目录的 config/mcp.php
        // 由 php-mcp/laravel 包自动加载
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // 注册中间件
        $this->registerMiddleware();

        // 注意：Resources 和 Tools 通过 php-mcp/laravel 的自动发现机制注册
        // 不需要手动注册，包会自动扫描配置的目录并发现带有注解的类
        // 配置文件使用项目根目录的 config/mcp.php
    }

    /**
     * 注册中间件
     */
    protected function registerMiddleware(): void
    {
        // 注册MCP认证中间件到应用中间件别名
        // 实际的中间件注册在 bootstrap/app.php 中完成
    }
}
