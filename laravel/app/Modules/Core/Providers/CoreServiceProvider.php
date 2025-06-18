<?php

namespace App\Modules\Core\Providers;

use Illuminate\Support\ServiceProvider;
use App\Modules\Core\Contracts\LogInterface;
use App\Modules\Core\Contracts\EventInterface;
use App\Modules\Core\Contracts\CacheInterface;
use App\Modules\Core\Contracts\ConfigInterface;
use App\Modules\Core\Contracts\ValidationInterface;
use App\Modules\Core\Services\LogService;
use App\Modules\Core\Services\EventService;
use App\Modules\Core\Services\CacheService;
use App\Modules\Core\Services\ConfigService;
use App\Modules\Core\Services\ValidationService;

class CoreServiceProvider extends ServiceProvider
{
    /**
     * 注册服务
     */
    public function register(): void
    {
        // 注册核心服务接口绑定
        $this->registerCoreServices();

        // 注册配置
        $this->registerConfig();
    }

    /**
     * 启动服务
     */
    public function boot(): void
    {
        // 注册中间件
        $this->registerMiddleware();

        // 加载路由
        $this->loadRoutes();

        // 发布配置
        $this->publishConfig();
    }

    /**
     * 注册核心服务
     */
    protected function registerCoreServices(): void
    {
        // 注册日志服务
        $this->app->singleton(LogInterface::class, LogService::class);

        // 注册事件服务
        $this->app->singleton(EventInterface::class, EventService::class);

        // 注册缓存服务
        $this->app->singleton(CacheInterface::class, CacheService::class);

        // 注册配置服务
        $this->app->singleton(ConfigInterface::class, ConfigService::class);

        // 注册验证服务
        $this->app->singleton(ValidationInterface::class, ValidationService::class);
    }

    /**
     * 注册配置
     */
    protected function registerConfig(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/core.php',
            'core'
        );
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

    /**
     * 加载路由
     */
    protected function loadRoutes(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
    }

    /**
     * 发布配置
     */
    protected function publishConfig(): void
    {
        $this->publishes([
            __DIR__ . '/../config/core.php' => config_path('core.php'),
        ], 'core-config');
    }
}
