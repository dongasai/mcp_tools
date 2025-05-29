<?php

namespace App\Modules\Core\Providers;

use Illuminate\Support\ServiceProvider;
use App\Modules\Core\Services\ConfigService;
use App\Modules\Core\Services\CacheService;
use App\Modules\Core\Services\LogService;
use App\Modules\Core\Services\EventService;
use App\Modules\Core\Services\ValidationService;
use App\Modules\Core\Contracts\ConfigInterface;
use App\Modules\Core\Contracts\CacheInterface;
use App\Modules\Core\Contracts\LogInterface;
use App\Modules\Core\Contracts\EventInterface;
use App\Modules\Core\Contracts\ValidationInterface;

class CoreServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // 绑定核心服务接口
        $this->app->singleton(ConfigInterface::class, ConfigService::class);
        $this->app->singleton(CacheInterface::class, CacheService::class);
        $this->app->singleton(LogInterface::class, LogService::class);
        $this->app->singleton(EventInterface::class, EventService::class);
        $this->app->singleton(ValidationInterface::class, ValidationService::class);

        // 注册配置文件
        $this->mergeConfigFrom(
            __DIR__ . '/../config/core.php',
            'core'
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // 发布配置文件
        $this->publishes([
            __DIR__ . '/../config/core.php' => config_path('core.php'),
        ], 'core-config');

        // 加载路由
        $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');

        // 加载迁移
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // 注册中间件
        $this->registerMiddleware();
    }

    /**
     * 注册中间件
     */
    protected function registerMiddleware(): void
    {
        $router = $this->app['router'];
        
        // 注册全局中间件
        $router->aliasMiddleware('log.request', \App\Modules\Core\Middleware\LogRequestMiddleware::class);
        $router->aliasMiddleware('validate.request', \App\Modules\Core\Middleware\ValidateRequestMiddleware::class);
    }
}
