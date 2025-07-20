<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * 注册应用服务
     */
    public function register(): void
    {
        //
    }

    /**
     * 启动应用服务
     */
    public function boot(): void
    {
        // 设置默认字符串长度，以兼容MySQL的索引限制
        Schema::defaultStringLength(191);
        
        // 注册自定义日志驱动
        $this->app->make('log')->extend('size_rotating_daily', function ($app, $config) {
            $logger = new \App\Core\Logging\SizeRotatingDailyLogger();
            return $logger($config);
        });
        //
    }
}
