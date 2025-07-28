<?php

namespace DcatAdminDemo\Providers;

use Illuminate\Support\ServiceProvider;

class MAdminDemoServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // 合并配置文件
        $this->mergeConfigFrom(
            __DIR__ . '/../config/madmindemo.php',
            'madmindemo'
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // 加载路由
        $this->loadRoutesFrom(__DIR__ . '/../routes/admin.php');
        
        // 加载视图
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'madmindemo');
        
        // 加载迁移文件
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        
        // 发布配置文件
        $this->publishes([
            __DIR__ . '/../config/madmindemo.php' => config_path('madmindemo.php'),
        ], 'madmindemo-config');
    }
}