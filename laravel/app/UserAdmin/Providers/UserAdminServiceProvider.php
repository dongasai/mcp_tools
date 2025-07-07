<?php

namespace App\UserAdmin\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Router;

class UserAdminServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->registerMiddleware();
    }

    /**
     * 注册中间件
     */
    protected function registerMiddleware(): void
    {
        $router = $this->app['router'];

        // 注册用户后台资源归属验证中间件
        $router->aliasMiddleware(
            'user-admin.resource-ownership', 
            \App\UserAdmin\Middleware\EnsureResourceOwnership::class
        );
    }
}
