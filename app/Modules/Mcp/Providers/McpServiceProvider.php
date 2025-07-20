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

        // 注册配置文件
        $this->mergeConfigFrom(
            __DIR__ . '/../config/mcp.php',
            'mcp'
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // 发布配置文件
        $this->publishes([
            __DIR__ . '/../config/mcp.php' => config_path('mcp.php'),
        ], 'mcp-config');

        // 加载路由
        $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');

        // 注册MCP资源和工具
        $this->registerMcpResources();
        $this->registerMcpTools();
    }

    /**
     * 注册MCP资源
     */
    protected function registerMcpResources(): void
    {
        // 注册项目资源
        $this->app->bind('mcp.resource.project', \App\Modules\Mcp\Resources\ProjectResource::class);
        
        // 注册任务资源
        $this->app->bind('mcp.resource.task', \App\Modules\Mcp\Resources\TaskResource::class);
        
        // 注册Agent资源
        $this->app->bind('mcp.resource.agent', \App\Modules\Mcp\Resources\AgentResource::class);
    }

    /**
     * 注册MCP工具
     */
    protected function registerMcpTools(): void
    {
        // 注册项目工具
        $this->app->bind('mcp.tool.project', \App\Modules\Mcp\Tools\ProjectTool::class);
        
        // 注册任务工具
        $this->app->bind('mcp.tool.task', \App\Modules\Mcp\Tools\TaskTool::class);
        
        // 注册Agent工具
        $this->app->bind('mcp.tool.agent', \App\Modules\Mcp\Tools\AgentTool::class);
    }
}
