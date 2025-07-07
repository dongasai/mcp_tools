<?php

namespace App\Modules\UserAdmin\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Dcat\Admin\Admin;

class UserAdminServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // 注册配置文件
        $this->mergeConfigFrom(
            __DIR__ . '/../config/user-admin.php',
            'user-admin'
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // 发布配置文件
        $this->publishes([
            __DIR__ . '/../config/user-admin.php' => config_path('user-admin.php'),
        ], 'user-admin-config');

        // 注册路由
        $this->registerRoutes();
        
        // 注册视图
        $this->registerViews();
        
        // 注册中间件
        $this->registerMiddleware();
        
        // 配置用户后台
        $this->configureUserAdmin();
    }

    /**
     * 注册路由
     */
    protected function registerRoutes(): void
    {
        Route::group([
            'prefix' => config('user-admin.route.prefix', 'user-admin'),
            'namespace' => config('user-admin.route.namespace'),
            'middleware' => config('user-admin.route.middleware'),
        ], function () {
            $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        });
    }

    /**
     * 注册视图
     */
    protected function registerViews(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'user-admin');
        
        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/user-admin'),
        ], 'user-admin-views');
    }

    /**
     * 注册中间件
     */
    protected function registerMiddleware(): void
    {
        $router = $this->app['router'];
        
        // 注册用户后台认证中间件
        $router->aliasMiddleware('user-admin.auth', \App\Modules\UserAdmin\Middleware\UserAdminAuth::class);
        $router->aliasMiddleware('user-admin.permission', \App\Modules\UserAdmin\Middleware\UserAdminPermission::class);
    }

    /**
     * 配置用户后台
     */
    protected function configureUserAdmin(): void
    {
        // 这里可以添加用户后台的特殊配置
        // 比如自定义主题、菜单等
        
        // 如果需要，可以在这里配置独立的Admin实例
        // 但目前我们先使用共享的Admin配置
    }
}
