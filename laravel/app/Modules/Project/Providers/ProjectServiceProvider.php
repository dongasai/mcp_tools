<?php

namespace App\Modules\Project\Providers;

use Illuminate\Support\ServiceProvider;
use App\Modules\Project\Services\ProjectService;

class ProjectServiceProvider extends ServiceProvider
{
    /**
     * 注册服务
     */
    public function register(): void
    {
        // 注册Project服务
        $this->app->singleton(ProjectService::class);

        // 注册配置文件
        $this->mergeConfigFrom(
            __DIR__ . '/../config/project.php',
            'project'
        );
    }

    /**
     * 启动服务
     */
    public function boot(): void
    {
        // 发布配置文件
        $this->publishes([
            __DIR__ . '/../config/project.php' => config_path('project.php'),
        ], 'project-config');

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

        // Project创建事件
        $events->listen(
            \App\Modules\Project\Events\ProjectCreated::class,
            \App\Modules\Project\Listeners\SendProjectCreatedNotification::class
        );

        // Project状态变更事件
        $events->listen(
            \App\Modules\Project\Events\ProjectStatusChanged::class,
            \App\Modules\Project\Listeners\HandleProjectStatusChange::class
        );

        // Project Agent变更事件
        $events->listen(
            \App\Modules\Project\Events\ProjectAgentChanged::class,
            \App\Modules\Project\Listeners\HandleProjectAgentChange::class
        );

        // Project删除事件
        $events->listen(
            \App\Modules\Project\Events\ProjectDeleted::class,
            \App\Modules\Project\Listeners\CleanupProjectData::class
        );
    }

    /**
     * 注册中间件
     */
    protected function registerMiddleware(): void
    {
        $router = $this->app['router'];

        // 注册Project相关中间件
        $router->aliasMiddleware('project.owner', \App\Modules\Project\Middleware\EnsureProjectOwner::class);
        $router->aliasMiddleware('project.active', \App\Modules\Project\Middleware\EnsureProjectActive::class);
    }
}
