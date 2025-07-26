<?php

namespace App\Modules\User\Providers;

use Illuminate\Support\ServiceProvider;
use App\Modules\User\Services\UserService;
use App\Modules\User\Services\AuthService;
use App\Modules\User\Services\ProfileService;
use App\Modules\User\Services\SimpleAuthService;

class UserServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // 注册用户服务
        $this->app->singleton(UserService::class);
        $this->app->singleton(AuthService::class);
        $this->app->singleton(ProfileService::class);
        $this->app->singleton(SimpleAuthService::class);

        // 注册配置文件
        $this->mergeConfigFrom(
            __DIR__ . '/../config/user.php',
            'user'
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // 发布配置文件
        $this->publishes([
            __DIR__ . '/../config/user.php' => config_path('user.php'),
        ], 'user-config');

        // 加载路由
        // $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');

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

        // 用户创建事件
        $events->listen(
            \App\Modules\User\Events\UserCreated::class,
            \App\Modules\User\Listeners\SendWelcomeEmail::class
        );

        // 用户邮箱验证事件
        $events->listen(
            \App\Modules\User\Events\UserEmailVerified::class,
            \App\Modules\User\Listeners\ActivateUser::class
        );

        // 用户状态变更事件
        $events->listen(
            \App\Modules\User\Events\UserStatusChanged::class,
            \App\Modules\User\Listeners\NotifyStatusChange::class
        );
    }

    /**
     * 注册中间件
     */
    protected function registerMiddleware(): void
    {
        $router = $this->app['router'];

        // 注册用户认证中间件
        $router->aliasMiddleware('auth.user', \App\Modules\User\Middleware\AuthenticateUser::class);
        $router->aliasMiddleware('admin', \App\Modules\User\Middleware\AdminMiddleware::class);
        $router->aliasMiddleware('verified', \App\Modules\User\Middleware\EnsureEmailIsVerified::class);
    }
}
