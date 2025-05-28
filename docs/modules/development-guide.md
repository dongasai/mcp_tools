# 模块开发指南

## 概述

本指南详细说明如何在MCP Tools项目中开发新模块，包括模块结构、开发规范、测试要求和部署流程。

## 模块开发流程

### 1. 模块规划阶段

#### 需求分析
- 明确模块的业务职责和边界
- 定义模块对外提供的接口
- 分析与其他模块的依赖关系
- 评估性能和安全要求

#### 设计文档
- 创建模块设计文档（参考现有模块文档）
- 定义数据模型和数据库结构
- 设计API接口和事件定义
- 制定测试计划

### 2. 模块创建阶段

#### 目录结构创建
```bash
# 创建模块目录
mkdir -p app/Modules/YourModule/{Models,Services,Controllers,Resources,Events,Listeners,Middleware,Contracts,Exceptions}

# 创建测试目录
mkdir -p tests/Feature/Modules/YourModule
mkdir -p tests/Unit/Modules/YourModule
```

#### 基础文件创建
```bash
# 创建服务提供者
php artisan make:provider YourModuleServiceProvider

# 创建核心服务
php artisan make:class App/Modules/YourModule/Services/YourModuleService

# 创建模型
php artisan make:model App/Modules/YourModule/Models/YourModel

# 创建控制器
php artisan make:controller App/Modules/YourModule/Controllers/YourController
```

### 3. 模块实现阶段

#### 服务提供者实现
```php
<?php

namespace App\Modules\YourModule\Providers;

use Illuminate\Support\ServiceProvider;

class YourModuleServiceProvider extends ServiceProvider
{
    /**
     * 注册服务
     */
    public function register(): void
    {
        // 绑定服务接口
        $this->app->bind(
            YourModuleServiceInterface::class,
            YourModuleService::class
        );
        
        // 合并配置
        $this->mergeConfigFrom(
            __DIR__ . '/../config/yourmodule.php',
            'yourmodule'
        );
    }
    
    /**
     * 启动服务
     */
    public function boot(): void
    {
        // 注册路由
        $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
        
        // 注册迁移
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        
        // 注册事件监听器
        $this->registerEventListeners();
        
        // 发布配置文件
        $this->publishes([
            __DIR__ . '/../config/yourmodule.php' => config_path('yourmodule.php'),
        ], 'yourmodule-config');
    }
    
    /**
     * 注册事件监听器
     */
    private function registerEventListeners(): void
    {
        // 注册模块事件监听器
    }
}
```

#### 核心服务实现
```php
<?php

namespace App\Modules\YourModule\Services;

use App\Modules\YourModule\Contracts\YourModuleServiceInterface;

class YourModuleService implements YourModuleServiceInterface
{
    public function __construct(
        private LoggerInterface $logger,
        private CacheInterface $cache
    ) {}
    
    /**
     * 核心业务方法
     */
    public function performAction(array $data): array
    {
        try {
            // 验证输入数据
            $this->validateData($data);
            
            // 执行业务逻辑
            $result = $this->executeBusinessLogic($data);
            
            // 记录日志
            $this->logger->info('Action performed successfully', [
                'module' => 'YourModule',
                'action' => 'performAction',
                'data' => $data,
            ]);
            
            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Action failed', [
                'module' => 'YourModule',
                'action' => 'performAction',
                'error' => $e->getMessage(),
                'data' => $data,
            ]);
            
            throw $e;
        }
    }
    
    /**
     * 验证数据
     */
    private function validateData(array $data): void
    {
        // 实现数据验证逻辑
    }
    
    /**
     * 执行业务逻辑
     */
    private function executeBusinessLogic(array $data): array
    {
        // 实现核心业务逻辑
        return [];
    }
}
```

## 开发规范

### 1. 命名规范

#### 类命名
- 模块名使用PascalCase：`UserModule`
- 服务类使用Service后缀：`UserService`
- 控制器使用Controller后缀：`UserController`
- 模型使用单数形式：`User`
- 事件使用过去时：`UserCreated`
- 监听器使用动词：`SendWelcomeEmail`

#### 方法命名
- 使用驼峰命名法：`getUserById`
- 布尔方法使用is/has/can前缀：`isActive`, `hasPermission`, `canAccess`
- 获取方法使用get前缀：`getUsers`
- 设置方法使用set前缀：`setStatus`

#### 常量命名
- 使用大写字母和下划线：`STATUS_ACTIVE`
- 按功能分组：`USER_STATUS_ACTIVE`, `USER_ROLE_ADMIN`

### 2. 代码结构规范

#### 服务类结构
```php
<?php

namespace App\Modules\YourModule\Services;

class YourService
{
    // 1. 依赖注入属性
    private SomeService $someService;
    
    // 2. 构造函数
    public function __construct(SomeService $someService)
    {
        $this->someService = $someService;
    }
    
    // 3. 公共方法（按字母顺序）
    public function create(array $data): Model
    {
        // 实现
    }
    
    public function delete(int $id): bool
    {
        // 实现
    }
    
    public function update(int $id, array $data): Model
    {
        // 实现
    }
    
    // 4. 私有方法（按字母顺序）
    private function validateData(array $data): void
    {
        // 实现
    }
}
```

#### 控制器结构
```php
<?php

namespace App\Modules\YourModule\Controllers;

class YourController extends Controller
{
    // 1. 依赖注入
    public function __construct(
        private YourService $yourService
    ) {}
    
    // 2. RESTful方法（按标准顺序）
    public function index(Request $request): JsonResponse
    {
        // 实现
    }
    
    public function store(CreateRequest $request): JsonResponse
    {
        // 实现
    }
    
    public function show(Model $model): JsonResponse
    {
        // 实现
    }
    
    public function update(UpdateRequest $request, Model $model): JsonResponse
    {
        // 实现
    }
    
    public function destroy(Model $model): JsonResponse
    {
        // 实现
    }
    
    // 3. 自定义方法（按字母顺序）
    public function customAction(Request $request): JsonResponse
    {
        // 实现
    }
}
```

### 3. 错误处理规范

#### 异常定义
```php
<?php

namespace App\Modules\YourModule\Exceptions;

use App\Modules\Core\Exceptions\CoreException;

class YourModuleException extends CoreException
{
    protected string $errorCode = 'YOUR_MODULE_ERROR';
    
    public static function notFound(string $resource): self
    {
        return new self("$resource not found", 404);
    }
    
    public static function validationFailed(array $errors): self
    {
        $exception = new self('Validation failed', 422);
        $exception->context = ['errors' => $errors];
        return $exception;
    }
}
```

#### 异常处理
```php
public function someMethod(): array
{
    try {
        // 业务逻辑
        return $this->performOperation();
    } catch (ValidationException $e) {
        throw YourModuleException::validationFailed($e->errors());
    } catch (\Exception $e) {
        $this->logger->error('Unexpected error', [
            'module' => 'YourModule',
            'method' => 'someMethod',
            'error' => $e->getMessage(),
        ]);
        
        throw new YourModuleException('Operation failed', 500, $e);
    }
}
```

### 4. 测试规范

#### 单元测试
```php
<?php

namespace Tests\Unit\Modules\YourModule\Services;

use Tests\TestCase;
use App\Modules\YourModule\Services\YourService;

class YourServiceTest extends TestCase
{
    private YourService $service;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(YourService::class);
    }
    
    /** @test */
    public function it_can_create_resource(): void
    {
        // Arrange
        $data = ['name' => 'Test Resource'];
        
        // Act
        $result = $this->service->create($data);
        
        // Assert
        $this->assertInstanceOf(YourModel::class, $result);
        $this->assertEquals('Test Resource', $result->name);
    }
    
    /** @test */
    public function it_throws_exception_for_invalid_data(): void
    {
        // Arrange
        $invalidData = [];
        
        // Assert
        $this->expectException(YourModuleException::class);
        
        // Act
        $this->service->create($invalidData);
    }
}
```

#### 功能测试
```php
<?php

namespace Tests\Feature\Modules\YourModule;

use Tests\TestCase;
use App\Models\User;

class YourModuleApiTest extends TestCase
{
    /** @test */
    public function it_can_create_resource_via_api(): void
    {
        // Arrange
        $user = User::factory()->create();
        $data = ['name' => 'Test Resource'];
        
        // Act
        $response = $this->actingAs($user)
            ->postJson('/api/your-module', $data);
        
        // Assert
        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'name' => 'Test Resource',
                ],
            ]);
        
        $this->assertDatabaseHas('your_models', $data);
    }
}
```

## 模块集成

### 1. 注册模块
在`config/app.php`中注册模块服务提供者：
```php
'providers' => [
    // ...
    App\Modules\YourModule\Providers\YourModuleServiceProvider::class,
],
```

### 2. 配置路由
创建模块路由文件`app/Modules/YourModule/routes/api.php`：
```php
<?php

use Illuminate\Support\Facades\Route;
use App\Modules\YourModule\Controllers\YourController;

Route::prefix('api/your-module')
    ->middleware(['api', 'auth:sanctum'])
    ->group(function () {
        Route::apiResource('resources', YourController::class);
        Route::post('resources/{resource}/custom-action', [YourController::class, 'customAction']);
    });
```

### 3. 数据库迁移
创建模块迁移文件：
```bash
php artisan make:migration create_your_models_table --path=app/Modules/YourModule/database/migrations
```

### 4. 配置文件
创建模块配置文件`app/Modules/YourModule/config/yourmodule.php`：
```php
<?php

return [
    'enabled' => env('YOUR_MODULE_ENABLED', true),
    'cache_ttl' => env('YOUR_MODULE_CACHE_TTL', 3600),
    'api_rate_limit' => env('YOUR_MODULE_RATE_LIMIT', 60),
    
    'features' => [
        'feature_a' => env('YOUR_MODULE_FEATURE_A', true),
        'feature_b' => env('YOUR_MODULE_FEATURE_B', false),
    ],
];
```

## 部署和维护

### 1. 模块发布
```bash
# 发布配置文件
php artisan vendor:publish --tag=yourmodule-config

# 运行迁移
php artisan migrate --path=app/Modules/YourModule/database/migrations

# 清除缓存
php artisan config:clear
php artisan route:clear
```

### 2. 监控和日志
- 为模块添加专门的日志通道
- 实现健康检查端点
- 添加性能监控指标
- 设置错误报警机制

### 3. 版本管理
- 使用语义化版本控制
- 维护变更日志
- 提供升级指南
- 保持向后兼容性

## 最佳实践

### 1. 性能优化
- 使用数据库索引优化查询
- 实现适当的缓存策略
- 使用队列处理耗时操作
- 避免N+1查询问题

### 2. 安全考虑
- 验证所有输入数据
- 实现适当的权限检查
- 使用参数化查询防止SQL注入
- 记录安全相关事件

### 3. 可维护性
- 编写清晰的文档
- 保持代码简洁和可读
- 使用设计模式提高代码质量
- 定期重构和优化代码

### 4. 可测试性
- 编写全面的测试用例
- 使用依赖注入提高可测试性
- 模拟外部依赖
- 保持高测试覆盖率

---

**相关文档**：
- [模块架构概述](./README.md)
- [核心模块文档](./core.md)
- [测试指南](../testing.md)
