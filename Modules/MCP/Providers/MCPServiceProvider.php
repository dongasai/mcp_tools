<?php

namespace Modules\MCP\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\MCP\Services\MCPService;
use Modules\MCP\Services\AgentService;

class MCPServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // 注册MCP服务
        $this->app->singleton(MCPService::class);

        // 注册Agent服务
        $this->app->singleton(AgentService::class);

        // 合并Agent配置文件
        $this->mergeConfigFrom(
            __DIR__ . '/../config/agent.php',
            'agent'
        );

        // 注意：MCP配置使用项目根目录的 config/mcp.php
        // 由 php-mcp/laravel 包自动加载
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // 发布Agent配置文件
        $this->publishes([
            __DIR__ . '/../config/agent.php' => config_path('agent.php'),
        ], 'agent-config');

        // 加载Agent迁移
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // 注册事件监听器
        $this->registerEventListeners();

        // 注册中间件
        $this->registerMiddleware();

        // 注册命令
        if ($this->app->runningInConsole()) {
            $this->commands([
                \Modules\MCP\Commands\GenerateTokenCommand::class,
                \Modules\MCP\Commands\ManagePermissionsCommand::class,
                \Modules\MCP\Commands\ProcessExpiredQuestionsCommand::class,
            ]);
        }

        // 注意：Resources 和 Tools 通过 php-mcp/laravel 的自动发现机制注册
        // 不需要手动注册，包会自动扫描配置的目录并发现带有注解的类
        // 配置文件使用项目根目录的 config/mcp.php
    }

    /**
     * 注册事件监听器
     */
    protected function registerEventListeners(): void
    {
        $events = $this->app['events'];

        // Agent创建事件
        $events->listen(
            \Modules\MCP\Events\AgentCreated::class,
            \Modules\MCP\Listeners\SendAgentCreatedNotification::class
        );

        // Agent状态变更事件
        $events->listen(
            \Modules\MCP\Events\AgentStatusChanged::class,
            \Modules\MCP\Listeners\HandleAgentStatusChange::class
        );

        // Agent激活事件
        $events->listen(
            \Modules\MCP\Events\AgentActivated::class,
            \Modules\MCP\Listeners\HandleAgentActivation::class
        );

        // Agent停用事件
        $events->listen(
            \Modules\MCP\Events\AgentDeactivated::class,
            \Modules\MCP\Listeners\HandleAgentDeactivation::class
        );

        // Agent删除事件
        $events->listen(
            \Modules\MCP\Events\AgentDeleted::class,
            \Modules\MCP\Listeners\CleanupAgentData::class
        );
    }

    /**
     * 注册中间件
     */
    protected function registerMiddleware(): void
    {
        $router = $this->app['router'];

        // 注册Agent相关中间件
        $router->aliasMiddleware('agent.owner', \Modules\MCP\Middleware\EnsureAgentOwner::class);
        $router->aliasMiddleware('agent.active', \Modules\MCP\Middleware\EnsureAgentActive::class);

        // 注册MCP认证中间件到应用中间件别名
        // 实际的中间件注册在 bootstrap/app.php 中完成
    }
}



