<?php

namespace App\Modules\Agent\Providers;

use Illuminate\Support\ServiceProvider;
use App\Modules\Agent\Services\AgentService;

class AgentServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // 注册Agent服务
        $this->app->singleton(AgentService::class);

        // 注册配置文件
        $this->mergeConfigFrom(
            __DIR__ . '/../config/agent.php',
            'agent'
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // 发布配置文件
        $this->publishes([
            __DIR__ . '/../config/agent.php' => config_path('agent.php'),
        ], 'agent-config');

        // 加载路由
        $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');

        // 加载迁移
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // 注册事件监听器
        $this->registerEventListeners();

        // 注册中间件
        $this->registerMiddleware();
    }

    /**
     * 注册事件监听器
     */
    protected function registerEventListeners(): void
    {
        $events = $this->app['events'];

        // Agent创建事件
        $events->listen(
            \App\Modules\Agent\Events\AgentCreated::class,
            \App\Modules\Agent\Listeners\SendAgentCreatedNotification::class
        );

        // Agent状态变更事件
        $events->listen(
            \App\Modules\Agent\Events\AgentStatusChanged::class,
            \App\Modules\Agent\Listeners\HandleAgentStatusChange::class
        );

        // Agent激活事件
        $events->listen(
            \App\Modules\Agent\Events\AgentActivated::class,
            \App\Modules\Agent\Listeners\HandleAgentActivation::class
        );

        // Agent停用事件
        $events->listen(
            \App\Modules\Agent\Events\AgentDeactivated::class,
            \App\Modules\Agent\Listeners\HandleAgentDeactivation::class
        );

        // Agent删除事件
        $events->listen(
            \App\Modules\Agent\Events\AgentDeleted::class,
            \App\Modules\Agent\Listeners\CleanupAgentData::class
        );
    }

    /**
     * 注册中间件
     */
    protected function registerMiddleware(): void
    {
        $router = $this->app['router'];

        // 注册Agent相关中间件
        $router->aliasMiddleware('agent.owner', \App\Modules\Agent\Middleware\EnsureAgentOwner::class);
        $router->aliasMiddleware('agent.active', \App\Modules\Agent\Middleware\EnsureAgentActive::class);
    }
}
