# SuperAdmin 超级管理员模块

## 概述

SuperAdmin超级管理员模块提供系统级的后台管理界面，专门为系统管理员设计，用于管理整个MCP Tools平台的运营、监控和配置。该模块基于dcat/laravel-admin构建，提供强大的管理功能和直观的操作界面。

## 职责范围

### 1. 全局用户管理
- 用户账户审核和管理
- 用户角色和权限分配
- 用户行为监控和分析
- 批量用户操作

### 2. Agent全局管理
- Agent注册审核
- Agent性能监控
- Agent权限管理
- 异常Agent处理

### 3. 系统配置管理
- 全局系统配置
- 功能开关控制
- 安全策略配置
- 集成服务配置

### 4. 监控和统计
- 系统性能监控
- 用户活跃度统计
- 任务执行统计
- 资源使用分析

### 5. 运营管理
- 公告和通知发布
- 系统维护管理
- 数据备份和恢复
- 日志查看和分析

## 目录结构

```
app/Modules/SuperAdmin/
├── Controllers/
│   ├── DashboardController.php     # 仪表板控制器
│   ├── UserController.php          # 用户管理控制器
│   ├── AgentController.php         # Agent管理控制器
│   ├── SystemController.php        # 系统配置控制器
│   ├── MonitorController.php       # 监控控制器
│   ├── StatisticsController.php    # 统计控制器
│   └── LogController.php           # 日志管理控制器
├── Models/
│   ├── AdminUser.php               # 管理员用户模型
│   ├── SystemConfig.php            # 系统配置模型
│   ├── SystemLog.php               # 系统日志模型
│   └── Announcement.php            # 公告模型
├── Services/
│   ├── SuperAdminService.php       # 超级管理员服务
│   ├── SystemMonitorService.php    # 系统监控服务
│   ├── StatisticsService.php       # 统计分析服务
│   └── SystemConfigService.php     # 系统配置服务
├── Widgets/
│   ├── SystemStatusWidget.php      # 系统状态组件
│   ├── UserStatsWidget.php         # 用户统计组件
│   ├── AgentStatsWidget.php        # Agent统计组件
│   └── PerformanceWidget.php       # 性能监控组件
├── Middleware/
│   ├── SuperAdminAuth.php          # 超级管理员认证
│   └── SystemMaintenance.php       # 系统维护模式
├── Providers/
│   └── SuperAdminServiceProvider.php # 服务提供者
└── config/
    └── super-admin.php              # 超级管理员配置
```

## 核心控制器

### 1. DashboardController - 仪表板

```php
<?php

namespace App\Modules\SuperAdmin\Controllers;

use Dcat\Admin\Controllers\AdminController;
use Dcat\Admin\Layout\Content;
use Dcat\Admin\Widgets\Card;
use Dcat\Admin\Widgets\Chart\Line;

class DashboardController extends AdminController
{
    public function index(Content $content): Content
    {
        return $content
            ->title('系统概览')
            ->description('MCP Tools 系统管理面板')
            ->body($this->buildSystemOverview())
            ->body($this->buildUserStatistics())
            ->body($this->buildAgentStatistics())
            ->body($this->buildPerformanceCharts());
    }
    
    /**
     * 构建系统概览
     */
    private function buildSystemOverview(): Card
    {
        $stats = app(StatisticsService::class)->getSystemOverview();
        
        return Card::make('系统概览', view('super-admin.dashboard.overview', compact('stats')));
    }
    
    /**
     * 构建用户统计
     */
    private function buildUserStatistics(): Card
    {
        $userStats = app(StatisticsService::class)->getUserStatistics();
        
        return Card::make('用户统计', view('super-admin.dashboard.user-stats', compact('userStats')));
    }
    
    /**
     * 构建Agent统计
     */
    private function buildAgentStatistics(): Card
    {
        $agentStats = app(StatisticsService::class)->getAgentStatistics();
        
        return Card::make('Agent统计', view('super-admin.dashboard.agent-stats', compact('agentStats')));
    }
    
    /**
     * 构建性能图表
     */
    private function buildPerformanceCharts(): Card
    {
        $performanceData = app(SystemMonitorService::class)->getPerformanceData();
        
        $chart = Line::make()
            ->title('系统性能趋势')
            ->data($performanceData);
            
        return Card::make('性能监控', $chart);
    }
}
```

### 2. UserController - 用户管理

```php
<?php

namespace App\Modules\SuperAdmin\Controllers;

use Dcat\Admin\Controllers\AdminController;
use Dcat\Admin\Grid;
use Dcat\Admin\Form;
use Dcat\Admin\Show;

class UserController extends AdminController
{
    protected $title = '用户管理';
    
    protected function grid(): Grid
    {
        return Grid::make(User::with(['profile', 'agents', 'projects']), function (Grid $grid) {
            $grid->column('id', 'ID')->sortable();
            $grid->column('name', '用户名')->copyable();
            $grid->column('email', '邮箱')->copyable();
            
            $grid->column('status', '状态')->using([
                'active' => '活跃',
                'inactive' => '非活跃',
                'suspended' => '已暂停',
                'pending' => '待审核',
            ])->label([
                'active' => 'success',
                'inactive' => 'secondary',
                'suspended' => 'danger',
                'pending' => 'warning',
            ]);
            
            $grid->column('agents_count', 'Agent数量')->display(function () {
                return $this->agents->count();
            });
            
            $grid->column('projects_count', '项目数量')->display(function () {
                return $this->projects->count();
            });
            
            $grid->column('last_login_at', '最后登录');
            $grid->column('created_at', '注册时间');
            
            // 筛选器
            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('status', '状态')->select([
                    'active' => '活跃',
                    'inactive' => '非活跃',
                    'suspended' => '已暂停',
                    'pending' => '待审核',
                ]);
                
                $filter->between('created_at', '注册时间')->datetime();
                $filter->between('last_login_at', '最后登录')->datetime();
            });
            
            // 批量操作
            $grid->batchActions([
                new BatchApproveUserAction(),
                new BatchSuspendUserAction(),
                new BatchDeleteUserAction(),
            ]);
            
            // 行操作
            $grid->actions(function (Grid\Displayers\Actions $actions) {
                $actions->add(new ViewUserDetailAction());
                $actions->add(new SendNotificationAction());
                
                if ($this->status === 'pending') {
                    $actions->add(new ApproveUserAction());
                }
                
                if ($this->status === 'active') {
                    $actions->add(new SuspendUserAction());
                }
            });
        });
    }
    
    protected function form(): Form
    {
        return Form::make(User::class, function (Form $form) {
            $form->text('name', '用户名')->required();
            $form->email('email', '邮箱')->required();
            
            $form->select('status', '状态')->options([
                'active' => '活跃',
                'inactive' => '非活跃',
                'suspended' => '已暂停',
                'pending' => '待审核',
            ])->default('pending');
            
            $form->multipleSelect('roles', '角色')->options(
                Role::pluck('display_name', 'name')
            );
            
            $form->textarea('admin_notes', '管理员备注')->rows(3);
            
            // 保存后回调
            $form->saved(function (Form $form) {
                if ($form->model()->status === 'active') {
                    // 发送账户激活通知
                    event(new UserActivated($form->model()));
                }
            });
        });
    }
    
    protected function detail($id): Show
    {
        return Show::make($id, User::with(['profile', 'agents', 'projects', 'tasks']), function (Show $show) {
            $show->field('name', '用户名');
            $show->field('email', '邮箱');
            $show->field('status', '状态')->using([
                'active' => '活跃',
                'inactive' => '非活跃',
                'suspended' => '已暂停',
                'pending' => '待审核',
            ]);
            
            $show->field('profile.full_name', '真实姓名');
            $show->field('profile.phone', '电话');
            $show->field('last_login_at', '最后登录');
            $show->field('created_at', '注册时间');
            
            $show->divider();
            
            // Agent列表
            $show->relation('agents', 'Agent列表', function ($model) {
                $grid = new Grid($model);
                $grid->column('agent_id', 'Agent ID');
                $grid->column('name', '名称');
                $grid->column('type', '类型');
                $grid->column('status', '状态')->label();
                $grid->column('last_active_at', '最后活跃');
                return $grid;
            });
            
            // 项目列表
            $show->relation('projects', '项目列表', function ($model) {
                $grid = new Grid($model);
                $grid->column('name', '项目名称');
                $grid->column('status', '状态')->label();
                $grid->column('tasks_count', '任务数量');
                $grid->column('created_at', '创建时间');
                return $grid;
            });
        });
    }
}
```

### 3. AgentController - Agent管理

```php
<?php

namespace App\Modules\SuperAdmin\Controllers;

class AgentController extends AdminController
{
    protected $title = 'Agent管理';
    
    protected function grid(): Grid
    {
        return Grid::make(Agent::with(['user', 'projects']), function (Grid $grid) {
            $grid->column('agent_id', 'Agent ID')->copyable();
            $grid->column('name', '名称');
            $grid->column('type', '类型')->using([
                'claude' => 'Claude',
                'gpt' => 'GPT',
                'custom' => '自定义',
            ])->label();
            
            $grid->column('user.name', '所属用户');
            
            $grid->column('status', '状态')->using([
                'active' => '活跃',
                'inactive' => '非活跃',
                'suspended' => '已暂停',
                'pending' => '待审核',
            ])->label([
                'active' => 'success',
                'inactive' => 'secondary',
                'suspended' => 'danger',
                'pending' => 'warning',
            ]);
            
            $grid->column('allowed_projects', '授权项目数')->display(function ($projects) {
                return is_array($projects) ? count($projects) : 0;
            });
            
            $grid->column('last_active_at', '最后活跃');
            $grid->column('created_at', '创建时间');
            
            // 性能指标
            $grid->column('performance', '性能评分')->display(function () {
                $score = app(StatisticsService::class)->getAgentPerformanceScore($this->id);
                return "<span class='badge badge-{$this->getScoreColor($score)}'>{$score}</span>";
            });
            
            // 筛选器
            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('status', '状态')->select([
                    'active' => '活跃',
                    'inactive' => '非活跃',
                    'suspended' => '已暂停',
                    'pending' => '待审核',
                ]);
                
                $filter->equal('type', '类型')->select([
                    'claude' => 'Claude',
                    'gpt' => 'GPT',
                    'custom' => '自定义',
                ]);
                
                $filter->where(function ($query) {
                    $query->whereHas('user', function ($q) {
                        $q->where('name', 'like', "%{$this->input}%");
                    });
                }, '用户名');
            });
            
            // 批量操作
            $grid->batchActions([
                new BatchApproveAgentAction(),
                new BatchSuspendAgentAction(),
                new BatchDeleteAgentAction(),
            ]);
            
            // 行操作
            $grid->actions(function (Grid\Displayers\Actions $actions) {
                $actions->add(new ViewAgentPerformanceAction());
                $actions->add(new ManageAgentPermissionsAction());
                
                if ($this->status === 'pending') {
                    $actions->add(new ApproveAgentAction());
                }
            });
        });
    }
    
    protected function form(): Form
    {
        return Form::make(Agent::class, function (Form $form) {
            $form->text('name', '名称')->required();
            $form->select('type', '类型')->options([
                'claude' => 'Claude',
                'gpt' => 'GPT',
                'custom' => '自定义',
            ])->required();
            
            $form->select('user_id', '所属用户')->options(
                User::pluck('name', 'id')
            )->required();
            
            $form->select('status', '状态')->options([
                'active' => '活跃',
                'inactive' => '非活跃',
                'suspended' => '已暂停',
                'pending' => '待审核',
            ])->default('pending');
            
            $form->multipleSelect('allowed_projects', '授权项目')->options(
                Project::pluck('name', 'id')
            );
            
            $form->checkbox('allowed_actions', '允许操作')->options([
                'read' => '读取',
                'create_task' => '创建任务',
                'update_task' => '更新任务',
                'claim_task' => '认领任务',
                'complete_task' => '完成任务',
                'sync_github' => '同步GitHub',
            ]);
            
            $form->number('token_expires_in', '令牌有效期(秒)')
                ->default(86400)
                ->min(3600)
                ->max(2592000);
                
            $form->textarea('admin_notes', '管理员备注')->rows(3);
        });
    }
}
```

### 4. SystemController - 系统配置

```php
<?php

namespace App\Modules\SuperAdmin\Controllers;

class SystemController extends AdminController
{
    protected $title = '系统配置';
    
    public function index(Content $content): Content
    {
        return $content
            ->title('系统配置')
            ->body($this->buildConfigTabs());
    }
    
    private function buildConfigTabs(): Tab
    {
        $tab = new Tab();
        
        $tab->add('基础配置', $this->buildBasicConfig());
        $tab->add('安全配置', $this->buildSecurityConfig());
        $tab->add('功能开关', $this->buildFeatureToggles());
        $tab->add('集成配置', $this->buildIntegrationConfig());
        $tab->add('性能配置', $this->buildPerformanceConfig());
        
        return $tab;
    }
    
    private function buildBasicConfig(): Form
    {
        return Form::make(SystemConfig::class, function (Form $form) {
            $form->text('site_name', '站点名称')->default('MCP Tools');
            $form->textarea('site_description', '站点描述');
            $form->url('site_url', '站点URL');
            $form->email('admin_email', '管理员邮箱');
            $form->select('timezone', '时区')->options(timezone_identifiers_list());
            $form->select('language', '默认语言')->options([
                'zh-CN' => '简体中文',
                'en' => 'English',
            ]);
        });
    }
    
    private function buildSecurityConfig(): Form
    {
        return Form::make(SystemConfig::class, function (Form $form) {
            $form->switch('user_registration_enabled', '允许用户注册')->default(1);
            $form->switch('email_verification_required', '邮箱验证必需')->default(1);
            $form->switch('agent_approval_required', 'Agent审核必需')->default(1);
            
            $form->number('max_login_attempts', '最大登录尝试次数')->default(5);
            $form->number('lockout_duration', '锁定时长(分钟)')->default(15);
            $form->number('session_lifetime', '会话有效期(分钟)')->default(120);
            
            $form->switch('two_factor_enabled', '启用双因素认证')->default(0);
            $form->switch('ip_whitelist_enabled', '启用IP白名单')->default(0);
            $form->textarea('allowed_ips', 'IP白名单')->rows(5);
        });
    }
    
    private function buildFeatureToggles(): Form
    {
        return Form::make(SystemConfig::class, function (Form $form) {
            $form->switch('mcp_server_enabled', '启用MCP服务器')->default(1);
            $form->switch('github_integration_enabled', '启用GitHub集成')->default(1);
            $form->switch('notification_enabled', '启用通知系统')->default(1);
            $form->switch('task_auto_assignment', '任务自动分配')->default(0);
            $form->switch('agent_auto_approval', 'Agent自动审核')->default(0);
            
            $form->number('max_projects_per_user', '每用户最大项目数')->default(10);
            $form->number('max_agents_per_user', '每用户最大Agent数')->default(5);
            $form->number('max_tasks_per_project', '每项目最大任务数')->default(1000);
        });
    }
}
```

## 系统监控服务

### SystemMonitorService

```php
<?php

namespace App\Modules\SuperAdmin\Services;

class SystemMonitorService
{
    /**
     * 获取系统状态
     */
    public function getSystemStatus(): array
    {
        return [
            'server' => $this->getServerStatus(),
            'database' => $this->getDatabaseStatus(),
            'cache' => $this->getCacheStatus(),
            'queue' => $this->getQueueStatus(),
            'storage' => $this->getStorageStatus(),
        ];
    }
    
    /**
     * 获取性能数据
     */
    public function getPerformanceData(): array
    {
        return [
            'cpu_usage' => $this->getCpuUsage(),
            'memory_usage' => $this->getMemoryUsage(),
            'disk_usage' => $this->getDiskUsage(),
            'response_time' => $this->getAverageResponseTime(),
            'throughput' => $this->getThroughput(),
        ];
    }
    
    /**
     * 获取服务器状态
     */
    private function getServerStatus(): array
    {
        return [
            'status' => 'online',
            'uptime' => $this->getUptime(),
            'load_average' => sys_getloadavg(),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
        ];
    }
    
    /**
     * 获取数据库状态
     */
    private function getDatabaseStatus(): array
    {
        try {
            DB::connection()->getPdo();
            $connectionCount = DB::select('SHOW STATUS LIKE "Threads_connected"')[0]->Value ?? 0;
            
            return [
                'status' => 'connected',
                'connections' => $connectionCount,
                'size' => $this->getDatabaseSize(),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * 获取缓存状态
     */
    private function getCacheStatus(): array
    {
        try {
            Cache::put('health_check', 'ok', 60);
            $result = Cache::get('health_check');
            
            return [
                'status' => $result === 'ok' ? 'working' : 'error',
                'driver' => config('cache.default'),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
        }
    }
}
```

## 统计分析服务

### StatisticsService

```php
<?php

namespace App\Modules\SuperAdmin\Services;

class StatisticsService
{
    /**
     * 获取系统概览统计
     */
    public function getSystemOverview(): array
    {
        return [
            'total_users' => User::count(),
            'active_users' => User::where('status', 'active')->count(),
            'total_agents' => Agent::count(),
            'active_agents' => Agent::where('status', 'active')->count(),
            'total_projects' => Project::count(),
            'total_tasks' => Task::count(),
            'completed_tasks' => Task::where('status', 'completed')->count(),
            'system_uptime' => $this->getSystemUptime(),
        ];
    }
    
    /**
     * 获取用户统计
     */
    public function getUserStatistics(): array
    {
        $now = now();
        
        return [
            'new_users_today' => User::whereDate('created_at', $now)->count(),
            'new_users_week' => User::whereBetween('created_at', [$now->subWeek(), $now])->count(),
            'new_users_month' => User::whereBetween('created_at', [$now->subMonth(), $now])->count(),
            'active_users_today' => User::whereDate('last_login_at', $now)->count(),
            'user_growth_trend' => $this->getUserGrowthTrend(),
            'user_activity_trend' => $this->getUserActivityTrend(),
        ];
    }
    
    /**
     * 获取Agent统计
     */
    public function getAgentStatistics(): array
    {
        return [
            'total_agents' => Agent::count(),
            'agents_by_type' => Agent::groupBy('type')->selectRaw('type, count(*) as count')->pluck('count', 'type'),
            'agents_by_status' => Agent::groupBy('status')->selectRaw('status, count(*) as count')->pluck('count', 'status'),
            'agent_performance_avg' => $this->getAverageAgentPerformance(),
            'top_performing_agents' => $this->getTopPerformingAgents(),
        ];
    }
    
    /**
     * 获取Agent性能评分
     */
    public function getAgentPerformanceScore(int $agentId): float
    {
        $agent = Agent::findOrFail($agentId);
        
        // 计算性能评分（基于任务完成率、响应时间等）
        $completedTasks = $agent->subTasks()->where('status', 'completed')->count();
        $totalTasks = $agent->subTasks()->count();
        $completionRate = $totalTasks > 0 ? ($completedTasks / $totalTasks) * 100 : 0;
        
        $avgResponseTime = $agent->subTasks()
            ->whereNotNull('actual_duration')
            ->avg('actual_duration');
            
        // 综合评分算法
        $score = ($completionRate * 0.7) + (max(0, 100 - ($avgResponseTime / 60)) * 0.3);
        
        return round($score, 1);
    }
}
```

## 配置文件

### super-admin.php

```php
<?php

// config/super-admin.php
return [
    'name' => 'MCP Tools 超级管理员',
    'logo' => '<b>MCP</b> Super Admin',
    'logo-mini' => '<b>SA</b>',
    
    'route' => [
        'prefix' => env('SUPER_ADMIN_ROUTE_PREFIX', 'super-admin'),
        'namespace' => 'App\\Modules\\SuperAdmin\\Controllers',
        'middleware' => ['web', 'super-admin.auth'],
    ],
    
    'auth' => [
        'guards' => [
            'super-admin' => [
                'driver' => 'session',
                'provider' => 'super-admin',
            ],
        ],
        'providers' => [
            'super-admin' => [
                'driver' => 'eloquent',
                'model' => App\Modules\SuperAdmin\Models\AdminUser::class,
            ],
        ],
    ],
    
    'permissions' => [
        'user_management' => '用户管理',
        'agent_management' => 'Agent管理',
        'system_config' => '系统配置',
        'system_monitor' => '系统监控',
        'data_export' => '数据导出',
        'system_maintenance' => '系统维护',
    ],
    
    'features' => [
        'user_approval' => env('SUPER_ADMIN_USER_APPROVAL', true),
        'agent_approval' => env('SUPER_ADMIN_AGENT_APPROVAL', true),
        'system_monitoring' => env('SUPER_ADMIN_MONITORING', true),
        'data_analytics' => env('SUPER_ADMIN_ANALYTICS', true),
    ],
];
```

---

**相关文档**：
- [用户后台模块](./user-admin.md)
- [用户模块](./user.md)
- [Agent模块](./agent.md)
