<?php

namespace App\Modules\Task\Providers;

use Illuminate\Support\ServiceProvider;
use App\Modules\Task\Services\TaskService;

class TaskServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        

        // 注册Task服务
        $this->app->singleton(TaskService::class);

        // 加载配置文件
        $this->mergeConfigFrom(
            __DIR__ . '/../../../../config/task.php',
            'task'
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // 发布配置文件
        $this->publishes([
            __DIR__ . '/../config/task.php' => config_path('task.php'),
        ], 'task-config');

        // 加载路由
        // $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');

        // 暂时注释掉迁移文件加载
        // $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // 注册事件监听器
        $this->registerEventListeners();

        // 注册命令
        $this->registerCommands();

        // 暂时注释掉中间件注册（需要先创建中间件类）
        // $this->registerMiddleware();
    }

    /**
     * 注册事件监听器
     */
    protected function registerEventListeners(): void
    {
        $events = $this->app['events'];

        // Task创建事件
        $events->listen(
            \App\Modules\Task\Events\TaskCreated::class,
            \App\Modules\Task\Listeners\SendTaskCreatedNotification::class
        );

        // Task状态变更事件
        $events->listen(
            \App\Modules\Task\Events\TaskStatusChanged::class,
            \App\Modules\Task\Listeners\HandleTaskStatusChange::class
        );

        // Task进度更新事件
        $events->listen(
            \App\Modules\Task\Events\TaskProgressUpdated::class,
            \App\Modules\Task\Listeners\HandleTaskProgressUpdate::class
        );

        // Task Agent变更事件
        $events->listen(
            \App\Modules\Task\Events\TaskAgentChanged::class,
            \App\Modules\Task\Listeners\HandleTaskAgentChange::class
        );

        // Task评论事件
        $events->listen(
            \App\Modules\Task\Events\TaskCommentCreated::class,
            [\App\Modules\Task\Listeners\HandleTaskCommentEvents::class, 'handleCommentCreated']
        );

        $events->listen(
            \App\Modules\Task\Events\TaskCommentUpdated::class,
            [\App\Modules\Task\Listeners\HandleTaskCommentEvents::class, 'handleCommentUpdated']
        );

        $events->listen(
            \App\Modules\Task\Events\TaskCommentDeleted::class,
            [\App\Modules\Task\Listeners\HandleTaskCommentEvents::class, 'handleCommentDeleted']
        );

        // TODO: 创建以下监听器类后启用
        // Task开始事件
        // $events->listen(
        //     \App\Modules\Task\Events\TaskStarted::class,
        //     \App\Modules\Task\Listeners\HandleTaskStart::class
        // );

        // Task完成事件
        // $events->listen(
        //     \App\Modules\Task\Events\TaskCompleted::class,
        //     \App\Modules\Task\Listeners\HandleTaskCompletion::class
        // );

        // Task删除事件
        // $events->listen(
        //     \App\Modules\Task\Events\TaskDeleted::class,
        //     \App\Modules\Task\Listeners\CleanupTaskData::class
        // );
    }

    /**
     * 注册命令
     */
    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            // 暂时没有命令需要注册
        }
    }

    /**
     * 注册中间件
     */
    protected function registerMiddleware(): void
    {
        $router = $this->app['router'];

        // 注册Task相关中间件
        $router->aliasMiddleware('task.owner', \App\Modules\Task\Middleware\EnsureTaskOwner::class);
        $router->aliasMiddleware('task.assigned', \App\Modules\Task\Middleware\EnsureTaskAssigned::class);
    }
}
