<?php

namespace App\Modules\Dbcont\Providers;

use App\Modules\Dbcont\Contracts\DatabaseConnectionInterface;
use App\Modules\Dbcont\Contracts\SqlExecutionInterface;
use App\Modules\Dbcont\Services\DatabaseConnectionService;
use App\Modules\Dbcont\Services\SqlExecutionService;
use App\Modules\Dbcont\Services\PermissionService;
use App\Modules\Dbcont\Services\SecurityService;
use Illuminate\Support\ServiceProvider;

class DbcontServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // 合并配置文件
        $this->mergeConfigFrom(
            __DIR__ . '/../config/dbcont.php',
            'dbcont'
        );

        // 注册服务
        $this->app->singleton(DatabaseConnectionInterface::class, DatabaseConnectionService::class);
        $this->app->singleton(SqlExecutionInterface::class, SqlExecutionService::class);
        $this->app->singleton(PermissionService::class);
        $this->app->singleton(SecurityService::class);
        $this->app->singleton(OperationLogService::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // 加载迁移文件
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // 发布配置文件
        $this->publishes([
            __DIR__ . '/../config/dbcont.php' => config_path('dbcont.php'),
        ], 'dbcont-config');
    }
}