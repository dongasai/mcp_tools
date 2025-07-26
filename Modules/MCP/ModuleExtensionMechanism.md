# MCP模块扩展机制详解

**文档版本**: 1.0.0  
**创建时间**: 2025年07月26日 04:00:00 CST  
**作者**: AI Assistant  
**项目**: MCP Tools  

## 概述

模块扩展机制是一种混合架构模式，它在保持MCP统一管理优势的基础上，允许业务模块通过标准化接口扩展自己的MCP功能。这种机制结合了统一管理和模块自治的优点，为项目提供了更大的灵活性。

## 核心设计理念

### 1. 分层架构

```
┌─────────────────────────────────────────────────────────────┐
│                    MCP统一管理层                              │
├─────────────────────────────────────────────────────────────┤
│  基础设施层  │  扩展管理层  │  统一接口层  │  监控管理层    │
├─────────────────────────────────────────────────────────────┤
│                    模块扩展层                                │
├─────────────────────────────────────────────────────────────┤
│  Task扩展   │  Agent扩展   │  Dbcont扩展  │  自定义扩展    │
├─────────────────────────────────────────────────────────────┤
│                    业务模块层                                │
└─────────────────────────────────────────────────────────────┘
```

### 2. 核心原则

- **统一基础设施**：认证、权限、错误处理、日志记录等核心功能统一管理
- **标准化扩展**：通过标准接口进行扩展，保证一致性
- **自动集成**：扩展内容自动集成到MCP发现和管理系统
- **向后兼容**：不影响现有MCP工具和资源的正常运行
- **安全隔离**：扩展模块之间相互隔离，避免相互影响

## 技术实现方案

### 1. 核心接口设计

#### MCPExtensionInterface - 扩展接口

```php
<?php

namespace App\Modules\MCP\Contracts;

interface MCPExtensionInterface
{
    /**
     * 注册模块的MCP工具
     * @return array 工具类名数组
     */
    public function registerTools(): array;
    
    /**
     * 注册模块的MCP资源
     * @return array 资源类名数组
     */
    public function registerResources(): array;
    
    /**
     * 获取模块命名空间前缀
     * @return string 如 'task_ext', 'agent_ext'
     */
    public function getNamespace(): string;
    
    /**
     * 获取扩展配置
     * @return array 扩展特定的配置项
     */
    public function getConfig(): array;
    
    /**
     * 检查扩展是否启用
     * @return bool
     */
    public function isEnabled(): bool;
    
    /**
     * 获取扩展依赖
     * @return array 依赖的其他扩展或服务
     */
    public function getDependencies(): array;
    
    /**
     * 扩展初始化
     * @return void
     */
    public function initialize(): void;
    
    /**
     * 扩展清理
     * @return void
     */
    public function cleanup(): void;
}
```

#### BaseMCPExtension - 扩展基类

```php
<?php

namespace App\Modules\MCP\Extensions;

use App\Modules\MCP\Contracts\MCPExtensionInterface;

abstract class BaseMCPExtension implements MCPExtensionInterface
{
    protected string $namespace;
    protected array $config = [];
    protected array $dependencies = [];
    protected bool $enabled = true;
    
    public function __construct(string $namespace, array $config = [])
    {
        $this->namespace = $namespace;
        $this->config = $config;
    }
    
    public function getNamespace(): string
    {
        return $this->namespace;
    }
    
    public function getConfig(): array
    {
        return $this->config;
    }
    
    public function isEnabled(): bool
    {
        return $this->enabled && ($this->config['enabled'] ?? true);
    }
    
    public function getDependencies(): array
    {
        return $this->dependencies;
    }
    
    public function initialize(): void
    {
        // 默认实现，子类可以重写
    }
    
    public function cleanup(): void
    {
        // 默认实现，子类可以重写
    }
    
    /**
     * 验证扩展配置
     * @return bool
     */
    protected function validateConfig(): bool
    {
        // 配置验证逻辑
        return true;
    }
    
    /**
     * 记录扩展日志
     * @param string $message
     * @param array $context
     */
    protected function log(string $message, array $context = []): void
    {
        logger()->info("[Extension:{$this->namespace}] {$message}", $context);
    }
}
```

### 2. 扩展管理系统

#### ExtensionRegistry - 扩展注册器

```php
<?php

namespace App\Modules\MCP\Services;

use App\Modules\MCP\Contracts\MCPExtensionInterface;
use Illuminate\Support\Collection;

class ExtensionRegistry
{
    private Collection $extensions;
    private array $toolMap = [];
    private array $resourceMap = [];
    
    public function __construct()
    {
        $this->extensions = collect();
    }
    
    /**
     * 注册扩展
     */
    public function register(MCPExtensionInterface $extension): void
    {
        if (!$extension->isEnabled()) {
            return;
        }
        
        $namespace = $extension->getNamespace();
        
        // 检查依赖
        $this->checkDependencies($extension);
        
        // 注册扩展
        $this->extensions->put($namespace, $extension);
        
        // 注册工具
        foreach ($extension->registerTools() as $toolClass) {
            $this->toolMap[$namespace][] = $toolClass;
        }
        
        // 注册资源
        foreach ($extension->registerResources() as $resourceClass) {
            $this->resourceMap[$namespace][] = $resourceClass;
        }
        
        // 初始化扩展
        $extension->initialize();
        
        logger()->info("Extension registered: {$namespace}");
    }
    
    /**
     * 获取所有已注册的扩展
     */
    public function getExtensions(): Collection
    {
        return $this->extensions;
    }
    
    /**
     * 获取扩展的工具
     */
    public function getExtensionTools(string $namespace): array
    {
        return $this->toolMap[$namespace] ?? [];
    }
    
    /**
     * 获取扩展的资源
     */
    public function getExtensionResources(string $namespace): array
    {
        return $this->resourceMap[$namespace] ?? [];
    }
    
    /**
     * 获取所有扩展工具
     */
    public function getAllExtensionTools(): array
    {
        $tools = [];
        foreach ($this->toolMap as $namespace => $toolClasses) {
            $tools = array_merge($tools, $toolClasses);
        }
        return $tools;
    }
    
    /**
     * 获取所有扩展资源
     */
    public function getAllExtensionResources(): array
    {
        $resources = [];
        foreach ($this->resourceMap as $namespace => $resourceClasses) {
            $resources = array_merge($resources, $resourceClasses);
        }
        return $resources;
    }
    
    /**
     * 检查扩展依赖
     */
    private function checkDependencies(MCPExtensionInterface $extension): void
    {
        foreach ($extension->getDependencies() as $dependency) {
            if (!$this->extensions->has($dependency)) {
                throw new \Exception("Extension dependency not found: {$dependency}");
            }
        }
    }
    
    /**
     * 卸载扩展
     */
    public function unregister(string $namespace): void
    {
        if ($extension = $this->extensions->get($namespace)) {
            $extension->cleanup();
            $this->extensions->forget($namespace);
            unset($this->toolMap[$namespace]);
            unset($this->resourceMap[$namespace]);
            
            logger()->info("Extension unregistered: {$namespace}");
        }
    }
}
```

#### ExtensionDiscoverer - 扩展发现器

```php
<?php

namespace App\Modules\MCP\Services;

use App\Modules\MCP\Contracts\MCPExtensionInterface;
use Illuminate\Support\Facades\File;

class ExtensionDiscoverer
{
    private ExtensionRegistry $registry;
    private array $discoveryPaths;

    public function __construct(ExtensionRegistry $registry)
    {
        $this->registry = $registry;
        $this->discoveryPaths = config('mcp.extension_paths', [
            'app/Modules/*/Extensions',
            'app/Extensions'
        ]);
    }

    /**
     * 发现并注册所有扩展
     */
    public function discoverAndRegister(): void
    {
        $extensions = $this->discoverExtensions();

        // 按依赖关系排序
        $sortedExtensions = $this->sortByDependencies($extensions);

        // 注册扩展
        foreach ($sortedExtensions as $extension) {
            try {
                $this->registry->register($extension);
            } catch (\Exception $e) {
                logger()->error("Failed to register extension: " . get_class($extension), [
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * 发现扩展
     */
    private function discoverExtensions(): array
    {
        $extensions = [];

        foreach ($this->discoveryPaths as $path) {
            $fullPath = base_path($path);
            if (!File::exists($fullPath)) {
                continue;
            }

            $files = File::allFiles($fullPath);
            foreach ($files as $file) {
                if ($file->getExtension() === 'php') {
                    $extension = $this->loadExtensionFromFile($file->getPathname());
                    if ($extension) {
                        $extensions[] = $extension;
                    }
                }
            }
        }

        return $extensions;
    }

    /**
     * 从文件加载扩展
     */
    private function loadExtensionFromFile(string $filePath): ?MCPExtensionInterface
    {
        try {
            require_once $filePath;

            // 获取文件中定义的类
            $classes = get_declared_classes();
            $beforeClasses = $this->getClassesBefore($filePath);
            $newClasses = array_diff($classes, $beforeClasses);

            foreach ($newClasses as $className) {
                $reflection = new \ReflectionClass($className);
                if ($reflection->implementsInterface(MCPExtensionInterface::class) &&
                    !$reflection->isAbstract()) {
                    return $reflection->newInstance();
                }
            }
        } catch (\Exception $e) {
            logger()->error("Failed to load extension from file: {$filePath}", [
                'error' => $e->getMessage()
            ]);
        }

        return null;
    }

    /**
     * 按依赖关系排序扩展
     */
    private function sortByDependencies(array $extensions): array
    {
        $sorted = [];
        $visited = [];
        $visiting = [];

        foreach ($extensions as $extension) {
            $this->visitExtension($extension, $extensions, $sorted, $visited, $visiting);
        }

        return $sorted;
    }

    /**
     * 访问扩展（拓扑排序）
     */
    private function visitExtension(
        MCPExtensionInterface $extension,
        array $allExtensions,
        array &$sorted,
        array &$visited,
        array &$visiting
    ): void {
        $namespace = $extension->getNamespace();

        if (isset($visited[$namespace])) {
            return;
        }

        if (isset($visiting[$namespace])) {
            throw new \Exception("Circular dependency detected: {$namespace}");
        }

        $visiting[$namespace] = true;

        // 先访问依赖
        foreach ($extension->getDependencies() as $dependency) {
            $dependencyExtension = $this->findExtensionByNamespace($dependency, $allExtensions);
            if ($dependencyExtension) {
                $this->visitExtension($dependencyExtension, $allExtensions, $sorted, $visited, $visiting);
            }
        }

        unset($visiting[$namespace]);
        $visited[$namespace] = true;
        $sorted[] = $extension;
    }

    /**
     * 根据命名空间查找扩展
     */
    private function findExtensionByNamespace(string $namespace, array $extensions): ?MCPExtensionInterface
    {
        foreach ($extensions as $extension) {
            if ($extension->getNamespace() === $namespace) {
                return $extension;
            }
        }
        return null;
    }

    /**
     * 获取文件加载前的类列表
     */
    private function getClassesBefore(string $filePath): array
    {
        static $classesBefore = null;
        if ($classesBefore === null) {
            $classesBefore = get_declared_classes();
        }
        return $classesBefore;
    }
}
```

### 3. 扩展工具和资源基类

#### ExtendedMCPTool - 扩展工具基类

```php
<?php

namespace App\Modules\MCP\Extensions;

use App\Modules\MCP\Tools\BaseMCPTool;

abstract class ExtendedMCPTool extends BaseMCPTool
{
    protected string $extensionNamespace;

    public function __construct(string $extensionNamespace)
    {
        parent::__construct();
        $this->extensionNamespace = $extensionNamespace;
    }

    /**
     * 获取扩展命名空间
     */
    public function getExtensionNamespace(): string
    {
        return $this->extensionNamespace;
    }

    /**
     * 生成带扩展前缀的工具名称
     */
    protected function getToolName(string $baseName): string
    {
        return $this->extensionNamespace . '_' . $baseName;
    }

    /**
     * 记录扩展工具日志
     */
    protected function logExtension(string $message, array $context = []): void
    {
        $context['extension'] = $this->extensionNamespace;
        $context['tool'] = static::class;
        $this->logger->info($message, $context);
    }

    /**
     * 扩展特定的错误处理
     */
    protected function handleExtensionError(\Exception $e): array
    {
        $this->logExtension('Extension tool error: ' . $e->getMessage(), [
            'exception' => $e
        ]);

        return [
            'success' => false,
            'error' => $e->getMessage(),
            'extension' => $this->extensionNamespace,
            'tool' => static::class
        ];
    }
}
```

#### ExtendedMCPResource - 扩展资源基类

```php
<?php

namespace App\Modules\MCP\Extensions;

use App\Modules\MCP\Resources\BaseMCPResource;

abstract class ExtendedMCPResource extends BaseMCPResource
{
    protected string $extensionNamespace;

    public function __construct(string $extensionNamespace)
    {
        parent::__construct();
        $this->extensionNamespace = $extensionNamespace;
    }

    /**
     * 获取扩展命名空间
     */
    public function getExtensionNamespace(): string
    {
        return $this->extensionNamespace;
    }

    /**
     * 生成带扩展前缀的资源URI
     */
    protected function getResourceUri(string $baseUri): string
    {
        return $this->extensionNamespace . '://' . $baseUri;
    }

    /**
     * 记录扩展资源日志
     */
    protected function logExtension(string $message, array $context = []): void
    {
        $context['extension'] = $this->extensionNamespace;
        $context['resource'] = static::class;
        logger()->info($message, $context);
    }
}
```

## 实际应用示例

### 1. Task模块扩展示例

#### TaskExtension - Task模块扩展

```php
<?php

namespace App\Modules\Task\Extensions;

use App\Modules\MCP\Extensions\BaseMCPExtension;

class TaskExtension extends BaseMCPExtension
{
    public function __construct()
    {
        parent::__construct('task_ext', [
            'enabled' => true,
            'features' => ['analytics', 'reporting', 'automation']
        ]);
    }

    public function registerTools(): array
    {
        return [
            TaskAnalyticsTool::class,
            TaskReportTool::class,
            TaskAutomationTool::class,
        ];
    }

    public function registerResources(): array
    {
        return [
            TaskMetricsResource::class,
            TaskHistoryResource::class,
        ];
    }

    public function getDependencies(): array
    {
        return []; // 无依赖
    }

    public function initialize(): void
    {
        $this->log('Task extension initialized');

        // 初始化扩展特定的服务
        $this->initializeAnalyticsService();
        $this->initializeReportingService();
    }

    private function initializeAnalyticsService(): void
    {
        // 初始化任务分析服务
    }

    private function initializeReportingService(): void
    {
        // 初始化报告服务
    }
}
```

#### TaskAnalyticsTool - 任务分析工具

```php
<?php

namespace App\Modules\Task\Extensions;

use App\Modules\MCP\Extensions\ExtendedMCPTool;
use PhpMCP\Server\Attributes\MCPTool;

class TaskAnalyticsTool extends ExtendedMCPTool
{
    public function __construct()
    {
        parent::__construct('task_ext');
    }

    /**
     * 分析任务性能
     */
    #[MCPTool(name: 'task_ext_analyze_performance')]
    public function analyzePerformance(string $projectId, ?string $timeRange = null): array
    {
        try {
            $this->logExtension('Analyzing task performance', [
                'project_id' => $projectId,
                'time_range' => $timeRange
            ]);

            // 获取当前Agent
            $agent = $this->getCurrentAgent();

            // 验证权限
            $this->validatePermission('analyze_tasks');

            // 执行分析逻辑
            $analytics = $this->performAnalysis($projectId, $timeRange);

            return [
                'success' => true,
                'data' => $analytics,
                'extension' => $this->extensionNamespace
            ];

        } catch (\Exception $e) {
            return $this->handleExtensionError($e);
        }
    }

    /**
     * 生成任务趋势报告
     */
    #[MCPTool(name: 'task_ext_trend_report')]
    public function generateTrendReport(string $projectId, array $metrics = []): array
    {
        try {
            $this->logExtension('Generating trend report', [
                'project_id' => $projectId,
                'metrics' => $metrics
            ]);

            // 实现趋势分析逻辑
            $report = $this->generateTrends($projectId, $metrics);

            return [
                'success' => true,
                'data' => $report,
                'extension' => $this->extensionNamespace
            ];

        } catch (\Exception $e) {
            return $this->handleExtensionError($e);
        }
    }

    private function performAnalysis(string $projectId, ?string $timeRange): array
    {
        // 实际的分析逻辑
        return [
            'completion_rate' => 85.5,
            'average_duration' => '2.5 days',
            'bottlenecks' => ['code_review', 'testing'],
            'recommendations' => [
                'Optimize code review process',
                'Automate testing pipeline'
            ]
        ];
    }

    private function generateTrends(string $projectId, array $metrics): array
    {
        // 实际的趋势生成逻辑
        return [
            'period' => 'last_30_days',
            'trends' => [
                'task_creation' => [
                    'direction' => 'up',
                    'percentage' => 15.2
                ],
                'completion_time' => [
                    'direction' => 'down',
                    'percentage' => 8.7
                ]
            ]
        ];
    }
}
```

#### TaskMetricsResource - 任务指标资源

```php
<?php

namespace App\Modules\Task\Extensions;

use App\Modules\MCP\Extensions\ExtendedMCPResource;
use PhpMCP\Server\Attributes\MCPResource;

class TaskMetricsResource extends ExtendedMCPResource
{
    public function __construct()
    {
        parent::__construct('task_ext');
    }

    /**
     * 获取任务指标
     */
    #[MCPResource(uri: 'task_ext://metrics/{projectId}', name: 'task_ext_metrics')]
    public function getTaskMetrics(string $projectId): array
    {
        try {
            $this->logExtension('Fetching task metrics', [
                'project_id' => $projectId
            ]);

            // 获取指标数据
            $metrics = $this->fetchMetrics($projectId);

            return [
                'success' => true,
                'data' => $metrics,
                'extension' => $this->extensionNamespace,
                'uri' => $this->getResourceUri("metrics/{$projectId}")
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'extension' => $this->extensionNamespace
            ];
        }
    }

    private function fetchMetrics(string $projectId): array
    {
        // 实际的指标获取逻辑
        return [
            'total_tasks' => 156,
            'completed_tasks' => 134,
            'in_progress_tasks' => 18,
            'pending_tasks' => 4,
            'completion_rate' => 85.9,
            'average_completion_time' => '2.3 days',
            'last_updated' => now()->toISOString()
        ];
    }
}
```

### 2. 扩展配置

#### config/mcp_extensions.php

```php
<?php

return [

    /*
    |--------------------------------------------------------------------------
    | 扩展发现路径
    |--------------------------------------------------------------------------
    */
    'discovery_paths' => [
        'app/Modules/*/Extensions',
        'app/Extensions',
        'packages/*/src/Extensions'
    ],

    /*
    |--------------------------------------------------------------------------
    | 扩展配置
    |--------------------------------------------------------------------------
    */
    'extensions' => [
        'task_ext' => [
            'enabled' => true,
            'config' => [
                'analytics_enabled' => true,
                'reporting_enabled' => true,
                'cache_ttl' => 3600
            ]
        ],
        'agent_ext' => [
            'enabled' => true,
            'config' => [
                'monitoring_enabled' => true,
                'alert_threshold' => 0.8
            ]
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | 扩展安全设置
    |--------------------------------------------------------------------------
    */
    'security' => [
        'sandbox_mode' => false,
        'allowed_namespaces' => [
            'task_ext',
            'agent_ext',
            'report_ext'
        ],
        'max_execution_time' => 30,
        'memory_limit' => '128M'
    ],

    /*
    |--------------------------------------------------------------------------
    | 扩展监控设置
    |--------------------------------------------------------------------------
    */
    'monitoring' => [
        'enabled' => true,
        'log_level' => 'info',
        'performance_tracking' => true,
        'error_reporting' => true
    ]
];
```

## 集成到现有系统

### 1. 修改MCP服务提供者

```php
<?php

namespace App\Modules\MCP\Providers;

use Illuminate\Support\ServiceProvider;
use App\Modules\MCP\Services\ExtensionRegistry;
use App\Modules\MCP\Services\ExtensionDiscoverer;

class MCPServiceProvider extends ServiceProvider
{
    public function register()
    {
        // 注册扩展服务
        $this->app->singleton(ExtensionRegistry::class);
        $this->app->singleton(ExtensionDiscoverer::class);

        // 现有的MCP服务注册...
    }

    public function boot()
    {
        // 发现并注册扩展
        if ($this->app->runningInConsole() || config('mcp.auto_discover_extensions', true)) {
            $discoverer = $this->app->make(ExtensionDiscoverer::class);
            $discoverer->discoverAndRegister();
        }

        // 现有的MCP启动逻辑...
    }
}
```

### 2. 修改MCP发现器

```php
<?php

namespace App\Modules\MCP\Services;

use PhpMCP\Server\Utils\Discoverer;

class EnhancedMCPDiscoverer extends Discoverer
{
    private ExtensionRegistry $extensionRegistry;

    public function __construct(ExtensionRegistry $extensionRegistry)
    {
        $this->extensionRegistry = $extensionRegistry;
        parent::__construct();
    }

    public function discoverTools(): array
    {
        // 发现核心工具
        $coreTools = parent::discoverTools();

        // 发现扩展工具
        $extensionTools = $this->extensionRegistry->getAllExtensionTools();

        return array_merge($coreTools, $extensionTools);
    }

    public function discoverResources(): array
    {
        // 发现核心资源
        $coreResources = parent::discoverResources();

        // 发现扩展资源
        $extensionResources = $this->extensionRegistry->getAllExtensionResources();

        return array_merge($coreResources, $extensionResources);
    }
}
```

## 实施指南

### 第一阶段：基础架构搭建（1-2周）

#### 1.1 创建核心接口和基类
- [ ] 创建 `MCPExtensionInterface` 接口
- [ ] 实现 `BaseMCPExtension` 抽象基类
- [ ] 创建 `ExtendedMCPTool` 和 `ExtendedMCPResource` 基类

#### 1.2 实现扩展管理系统
- [ ] 开发 `ExtensionRegistry` 服务
- [ ] 实现 `ExtensionDiscoverer` 自动发现机制
- [ ] 创建扩展配置管理系统

#### 1.3 集成到现有系统
- [ ] 修改 `MCPServiceProvider` 支持扩展
- [ ] 增强 MCP 发现器支持扩展工具和资源
- [ ] 更新配置文件和环境变量

### 第二阶段：示例扩展开发（1周）

#### 2.1 创建Task模块扩展
- [ ] 实现 `TaskExtension` 扩展类
- [ ] 开发 `TaskAnalyticsTool` 示例工具
- [ ] 创建 `TaskMetricsResource` 示例资源

#### 2.2 测试和验证
- [ ] 编写扩展单元测试
- [ ] 进行集成测试
- [ ] 性能和安全测试

### 第三阶段：文档和培训（1周）

#### 3.1 编写开发文档
- [ ] 扩展开发指南
- [ ] API参考文档
- [ ] 最佳实践指南

#### 3.2 团队培训
- [ ] 技术分享会
- [ ] 实践工作坊
- [ ] 代码审查标准

### 第四阶段：逐步迁移（2-4周）

#### 4.1 识别迁移候选
- [ ] 分析现有MCP工具和资源
- [ ] 识别适合迁移到扩展模式的功能
- [ ] 制定迁移计划

#### 4.2 逐步迁移
- [ ] 优先迁移低风险功能
- [ ] 保持向后兼容
- [ ] 监控迁移效果

## 优势和挑战

### 优势

1. **灵活性增强**
   - 业务模块可以根据需要扩展MCP功能
   - 支持第三方插件和自定义扩展
   - 保持核心系统的稳定性

2. **开发效率提升**
   - 标准化的扩展接口降低学习成本
   - 自动发现和注册减少配置工作
   - 统一的基础设施避免重复开发

3. **架构清晰**
   - 核心功能和扩展功能分离
   - 依赖关系明确
   - 便于维护和升级

4. **安全可控**
   - 扩展在受控环境中运行
   - 统一的权限和安全检查
   - 可以实现扩展级别的监控

### 挑战

1. **复杂性增加**
   - 系统架构变得更复杂
   - 调试和故障排查难度增加
   - 需要更多的测试覆盖

2. **性能考虑**
   - 扩展发现和加载可能影响启动时间
   - 运行时的扩展调用开销
   - 内存使用量可能增加

3. **兼容性维护**
   - 扩展接口的向后兼容性
   - 核心系统升级对扩展的影响
   - 版本管理复杂性

4. **开发规范**
   - 需要制定详细的扩展开发规范
   - 代码质量控制
   - 安全审查流程

## 监控和运维

### 1. 扩展监控指标

```php
// 扩展性能监控
$metrics = [
    'extension_load_time' => 'ms',
    'extension_memory_usage' => 'MB',
    'extension_tool_calls' => 'count',
    'extension_error_rate' => 'percentage',
    'extension_availability' => 'percentage'
];
```

### 2. 日志记录

```php
// 扩展日志格式
[2025-07-26 04:00:00] INFO: [Extension:task_ext] Tool called: task_ext_analyze_performance
[2025-07-26 04:00:01] ERROR: [Extension:agent_ext] Failed to load resource: agent_ext_monitoring
[2025-07-26 04:00:02] DEBUG: [Extension:report_ext] Dependency check passed
```

### 3. 健康检查

```php
// 扩展健康检查端点
GET /api/mcp/extensions/health
{
    "status": "healthy",
    "extensions": {
        "task_ext": {
            "status": "active",
            "tools": 3,
            "resources": 2,
            "last_activity": "2025-07-26T04:00:00Z"
        },
        "agent_ext": {
            "status": "error",
            "error": "Dependency not found: monitoring_service",
            "tools": 0,
            "resources": 0
        }
    }
}
```

## 总结

模块扩展机制是MCP Tools项目架构演进的重要一步，它在保持统一管理优势的同时，为业务模块提供了灵活的扩展能力。通过标准化的接口、自动化的发现机制和完善的管理系统，这种混合架构模式能够：

1. **平衡统一性和灵活性**：核心功能统一管理，业务功能灵活扩展
2. **提高开发效率**：标准化接口和自动化工具降低开发成本
3. **保证系统稳定性**：扩展在受控环境中运行，不影响核心系统
4. **支持长期演进**：为项目的持续发展提供可扩展的架构基础

实施这种机制需要分阶段进行，从基础架构搭建开始，逐步完善功能，最终实现完整的扩展生态系统。虽然会增加一定的复杂性，但长期来看，这种投资将为项目带来更大的灵活性和可维护性。

---

**文档状态**: ✅ 完成
**最后更新**: 2025年07月26日 04:00:00 CST
**相关文档**: [MCP内容管理模式分析](./A.md)
**下次审查**: 建议在实施第一阶段后进行文档更新
