# Core 核心模块

## 概述

Core模块是MCP Tools的基础设施层，提供所有其他模块依赖的核心服务和公共组件。它负责系统的基础功能，包括配置管理、日志记录、缓存服务、事件系统等。

## 职责范围

### 1. 配置管理
- 统一的配置加载和管理
- 环境变量处理
- 配置验证和默认值
- 动态配置更新

### 2. 日志服务
- 结构化日志记录
- 多渠道日志输出
- 日志级别控制
- 性能日志和审计日志

### 3. 缓存服务
- 多层缓存策略
- 缓存键管理
- 缓存失效机制
- 分布式缓存支持

### 4. 事件系统
- 事件定义和分发
- 异步事件处理
- 事件监听器管理
- 事件重试机制

### 5. 异常处理
- 统一异常处理
- 错误码定义
- 异常日志记录
- 用户友好的错误信息

## 目录结构

```
app/Modules/Core/
├── Services/
│   ├── ConfigService.php          # 配置服务
│   ├── CacheService.php           # 缓存服务
│   ├── LogService.php             # 日志服务
│   └── EventService.php           # 事件服务
├── Contracts/
│   ├── ConfigInterface.php        # 配置接口
│   ├── CacheInterface.php         # 缓存接口
│   ├── LogInterface.php           # 日志接口
│   └── EventInterface.php         # 事件接口
├── Events/
│   ├── SystemStarted.php          # 系统启动事件
│   ├── ConfigUpdated.php          # 配置更新事件
│   └── CacheCleared.php           # 缓存清理事件
├── Exceptions/
│   ├── CoreException.php          # 核心异常基类
│   ├── ConfigException.php        # 配置异常
│   └── CacheException.php         # 缓存异常
├── Providers/
│   ├── CoreServiceProvider.php    # 核心服务提供者
│   └── EventServiceProvider.php   # 事件服务提供者
├── Middleware/
│   ├── LogRequestMiddleware.php   # 请求日志中间件
│   └── CacheMiddleware.php        # 缓存中间件
└── Helpers/
    ├── ArrayHelper.php            # 数组工具
    ├── StringHelper.php           # 字符串工具
    └── DateHelper.php             # 日期工具
```

## 核心服务

### 1. ConfigService

```php
<?php

namespace App\Modules\Core\Services;

use App\Modules\Core\Contracts\ConfigInterface;

class ConfigService implements ConfigInterface
{
    /**
     * 获取配置值
     */
    public function get(string $key, mixed $default = null): mixed;
    
    /**
     * 设置配置值
     */
    public function set(string $key, mixed $value): void;
    
    /**
     * 检查配置是否存在
     */
    public function has(string $key): bool;
    
    /**
     * 获取所有配置
     */
    public function all(): array;
    
    /**
     * 验证配置
     */
    public function validate(array $rules): bool;
}
```

### 2. CacheService

```php
<?php

namespace App\Modules\Core\Services;

use App\Modules\Core\Contracts\CacheInterface;

class CacheService implements CacheInterface
{
    /**
     * 获取缓存值
     */
    public function get(string $key, mixed $default = null): mixed;
    
    /**
     * 设置缓存值
     */
    public function put(string $key, mixed $value, int $ttl = null): bool;
    
    /**
     * 删除缓存
     */
    public function forget(string $key): bool;
    
    /**
     * 清空缓存
     */
    public function flush(): bool;
    
    /**
     * 记住缓存值
     */
    public function remember(string $key, int $ttl, callable $callback): mixed;
    
    /**
     * 生成缓存键
     */
    public function generateKey(string $prefix, array $params): string;
}
```

### 3. LogService

```php
<?php

namespace App\Modules\Core\Services;

use App\Modules\Core\Contracts\LogInterface;

class LogService implements LogInterface
{
    /**
     * 记录调试信息
     */
    public function debug(string $message, array $context = []): void;
    
    /**
     * 记录信息
     */
    public function info(string $message, array $context = []): void;
    
    /**
     * 记录警告
     */
    public function warning(string $message, array $context = []): void;
    
    /**
     * 记录错误
     */
    public function error(string $message, array $context = []): void;
    
    /**
     * 记录性能日志
     */
    public function performance(string $operation, float $duration, array $context = []): void;
    
    /**
     * 记录审计日志
     */
    public function audit(string $action, string $user, array $data = []): void;
}
```

### 4. EventService

```php
<?php

namespace App\Modules\Core\Services;

use App\Modules\Core\Contracts\EventInterface;

class EventService implements EventInterface
{
    /**
     * 分发事件
     */
    public function dispatch(object $event): void;
    
    /**
     * 监听事件
     */
    public function listen(string $event, callable $listener): void;
    
    /**
     * 订阅事件
     */
    public function subscribe(string $subscriber): void;
    
    /**
     * 异步分发事件
     */
    public function dispatchAsync(object $event): void;
    
    /**
     * 批量分发事件
     */
    public function dispatchBatch(array $events): void;
}
```

## 配置管理

### 配置文件结构

```php
// config/core.php
return [
    'cache' => [
        'default_ttl' => env('CORE_CACHE_TTL', 3600),
        'prefix' => env('CORE_CACHE_PREFIX', 'mcp_core_'),
        'tags' => env('CORE_CACHE_TAGS', true),
    ],
    
    'logging' => [
        'channels' => [
            'performance' => 'daily',
            'audit' => 'database',
            'error' => 'stack',
        ],
        'level' => env('CORE_LOG_LEVEL', 'info'),
    ],
    
    'events' => [
        'async' => env('CORE_EVENTS_ASYNC', true),
        'retry_attempts' => env('CORE_EVENTS_RETRY', 3),
        'retry_delay' => env('CORE_EVENTS_DELAY', 5),
    ],
];
```

## 事件定义

### 系统事件

```php
<?php

namespace App\Modules\Core\Events;

class SystemStarted
{
    public function __construct(
        public readonly string $version,
        public readonly array $modules,
        public readonly \DateTime $startTime
    ) {}
}

class ConfigUpdated
{
    public function __construct(
        public readonly string $key,
        public readonly mixed $oldValue,
        public readonly mixed $newValue,
        public readonly string $updatedBy
    ) {}
}

class CacheCleared
{
    public function __construct(
        public readonly string $pattern,
        public readonly int $clearedCount,
        public readonly string $reason
    ) {}
}
```

## 异常处理

### 异常层次结构

```php
<?php

namespace App\Modules\Core\Exceptions;

abstract class CoreException extends \Exception
{
    protected string $errorCode;
    protected array $context;
    
    public function getErrorCode(): string
    {
        return $this->errorCode;
    }
    
    public function getContext(): array
    {
        return $this->context;
    }
}

class ConfigException extends CoreException
{
    protected string $errorCode = 'CORE_CONFIG_ERROR';
}

class CacheException extends CoreException
{
    protected string $errorCode = 'CORE_CACHE_ERROR';
}
```

## 中间件

### 请求日志中间件

```php
<?php

namespace App\Modules\Core\Middleware;

class LogRequestMiddleware
{
    public function handle($request, \Closure $next)
    {
        $startTime = microtime(true);
        
        // 记录请求开始
        app('core.log')->info('Request started', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
        
        $response = $next($request);
        
        $duration = microtime(true) - $startTime;
        
        // 记录请求完成
        app('core.log')->performance('Request completed', $duration, [
            'status' => $response->getStatusCode(),
            'memory' => memory_get_peak_usage(true),
        ]);
        
        return $response;
    }
}
```

## 工具类

### 数组工具

```php
<?php

namespace App\Modules\Core\Helpers;

class ArrayHelper
{
    /**
     * 深度合并数组
     */
    public static function deepMerge(array $array1, array $array2): array;
    
    /**
     * 获取嵌套值
     */
    public static function get(array $array, string $key, mixed $default = null): mixed;
    
    /**
     * 设置嵌套值
     */
    public static function set(array &$array, string $key, mixed $value): void;
    
    /**
     * 数组扁平化
     */
    public static function flatten(array $array, string $separator = '.'): array;
}
```

## 性能优化

### 1. 缓存策略
- L1缓存：内存缓存（APCu）
- L2缓存：Redis缓存
- L3缓存：数据库缓存

### 2. 事件优化
- 异步事件处理
- 事件批量处理
- 事件去重机制

### 3. 日志优化
- 日志缓冲写入
- 日志压缩存储
- 日志分级处理

## 监控指标

### 1. 性能指标
- 请求响应时间
- 内存使用情况
- 缓存命中率

### 2. 业务指标
- 事件处理数量
- 配置更新频率
- 异常发生率

### 3. 系统指标
- CPU使用率
- 磁盘I/O
- 网络延迟

---

**相关文档**：
- [MCP协议模块](./mcp.md)
- [Agent代理模块](./agent.md)
- [配置参考](../configuration.md)
