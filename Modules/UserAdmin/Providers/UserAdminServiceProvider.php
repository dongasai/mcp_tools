<?php

namespace Modules\UserAdmin\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Factory;

class UserAdminServiceProvider extends ServiceProvider
{
    /**
     * @var string $moduleName
     */
    protected string $moduleName = 'UserAdmin';

    /**
     * @var string $moduleNameLower
     */
    protected string $moduleNameLower = 'user-admin';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->moduleName, 'database/migrations'));

        // 注册dcat-admin路由
        $this->registerDcatAdminRoutes();
    }

    /**
     * 注册dcat-admin路由
     */
    protected function registerDcatAdminRoutes(): void
    {
        if (class_exists(\Dcat\Admin\Admin::class)) {
            // 确保user-admin配置已加载
            if (! config('user-admin')) {
                $this->mergeConfigFrom(
                    module_path($this->moduleName, 'config/user-admin.php'),
                    'user-admin'
                );
            }
            
            // 使用Application的routes方法注册多应用路由，这会正确处理auth路由
            \Dcat\Admin\Admin::app()->routes(function () {
                // 临时设置admin配置为user-admin配置，确保在路由注册时使用正确的配置
                $originalAdminConfig = config('admin');
                config(['admin' => config('user-admin')]);
                
                // 注册默认的admin路由（包含auth路由）
                \Dcat\Admin\Admin::routes();
                
                // 注册模块特定路由
                require module_path('UserAdmin', 'routes/user-admin.php');
                
                // 恢复原始配置
                config(['admin' => $originalAdminConfig]);
            });
        }
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
    }

    /**
     * Register config.
     */
    protected function registerConfig(): void
    {
        $this->publishes([
            module_path($this->moduleName, 'config/user-admin.php') => config_path($this->moduleNameLower . '.php'),
        ], 'config');
        
        // 合并配置
        $this->mergeConfigFrom(
            module_path($this->moduleName, 'config/user-admin.php'),
            'user-admin'
        );
    }

    /**
     * Register views.
     */
    public function registerViews(): void
    {
        $viewPath = resource_path('views/modules/' . $this->moduleNameLower);

        $sourcePath = module_path($this->moduleName, 'resources/views');

        $this->publishes([
            $sourcePath => $viewPath
        ], ['views', $this->moduleNameLower . '-module-views']);

        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->moduleNameLower);
    }

    /**
     * Register translations.
     */
    public function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/' . $this->moduleNameLower);

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->moduleNameLower);
        } else {
            $this->loadTranslationsFrom(module_path($this->moduleName, 'lang'), $this->moduleNameLower);
        }
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [];
    }

    private function getPublishableViewPaths(): array
    {
        $paths = [];
        foreach (config('view.paths', []) as $path) {
            if (is_dir($path . '/modules/' . $this->moduleNameLower)) {
                $paths[] = $path . '/modules/' . $this->moduleNameLower;
            }
        }
        return $paths;
    }
}