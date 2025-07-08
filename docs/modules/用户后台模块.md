# UserAdmin 用户后台模块

## 概述

UserAdmin用户后台模块为普通用户提供个人管理界面，用户可以通过该界面管理自己的项目、任务、Agent和个人资源。该模块基于dcat/laravel-admin构建，提供直观友好的用户体验。

## 职责范围

### 1. 个人项目管理
- 创建和管理个人项目
- 项目成员邀请和管理
- 项目设置和配置
- 项目统计和分析

### 2. 任务管理
- 查看和管理项目任务
- 任务进度跟踪
- 子任务监控
- 任务统计分析

### 3. Agent管理
- 注册和配置个人Agent
- Agent权限设置
- Agent性能监控
- Agent使用统计

### 4. 个人资源管理
- GitHub账户连接
- API密钥管理
- 个人设置配置
- 通知偏好设置

### 5. 数据分析
- 个人工作统计
- 项目进度分析
- Agent效率分析
- 使用趋势报告

## 目录结构

```
app/Modules/UserAdmin/
├── Controllers/
│   ├── DashboardController.php     # 用户仪表板
│   ├── ProjectController.php       # 项目管理
│   ├── TaskController.php          # 任务管理
│   ├── AgentController.php         # Agent管理
│   ├── GitHubController.php        # GitHub集成
│   ├── ProfileController.php       # 个人资料
│   └── SettingsController.php      # 个人设置
├── Models/
│   ├── UserProject.php             # 用户项目关联
│   ├── UserAgent.php               # 用户Agent关联
│   ├── UserGitHubConnection.php    # GitHub连接
│   └── UserPreference.php          # 用户偏好
├── Services/
│   ├── UserAdminService.php        # 用户后台服务
│   ├── ProjectManagementService.php # 项目管理服务
│   ├── AgentManagementService.php  # Agent管理服务
│   └── GitHubIntegrationService.php # GitHub集成服务
├── Widgets/
│   ├── ProjectStatsWidget.php      # 项目统计组件
│   ├── TaskProgressWidget.php      # 任务进度组件
│   ├── AgentStatusWidget.php       # Agent状态组件
│   └── ActivityTimelineWidget.php  # 活动时间线组件
├── Actions/
│   ├── ConnectGitHubAction.php     # 连接GitHub操作
│   ├── CreateProjectAction.php     # 创建项目操作
│   ├── RegisterAgentAction.php     # 注册Agent操作
│   └── ExportDataAction.php        # 导出数据操作
├── Middleware/
│   ├── UserAdminAuth.php           # 用户后台认证
│   └── ProjectOwnership.php        # 项目所有权验证
├── Providers/
│   └── UserAdminServiceProvider.php # 服务提供者
└── config/
    └── user-admin.php              # 用户后台配置
```

## 核心控制器

### 1. DashboardController - 用户仪表板

```php
<?php

namespace App\Modules\UserAdmin\Controllers;

use Dcat\Admin\Controllers\AdminController;
use Dcat\Admin\Layout\Content;
use Dcat\Admin\Widgets\Card;
use Dcat\Admin\Widgets\Chart\Doughnut;

class DashboardController extends AdminController
{
    public function index(Content $content): Content
    {
        $user = auth()->user();
        
        return $content
            ->title('我的工作台')
            ->description("欢迎回来，{$user->name}")
            ->body($this->buildQuickStats($user))
            ->body($this->buildProjectOverview($user))
            ->body($this->buildTaskProgress($user))
            ->body($this->buildAgentStatus($user))
            ->body($this->buildRecentActivity($user));
    }
    
    /**
     * 构建快速统计
     */
    private function buildQuickStats(User $user): Card
    {
        $stats = [
            'projects' => $user->projects()->count(),
            'active_tasks' => $user->tasks()->whereIn('status', ['pending', 'in_progress'])->count(),
            'completed_tasks' => $user->tasks()->where('status', 'completed')->count(),
            'agents' => $user->agents()->count(),
        ];
        
        return Card::make('概览统计', view('user-admin.dashboard.quick-stats', compact('stats')));
    }
    
    /**
     * 构建项目概览
     */
    private function buildProjectOverview(User $user): Card
    {
        $projects = $user->projects()->with(['tasks'])->get();
        
        return Card::make('我的项目', view('user-admin.dashboard.project-overview', compact('projects')));
    }
    
    /**
     * 构建任务进度
     */
    private function buildTaskProgress(User $user): Card
    {
        $taskStats = $user->tasks()
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
            
        $chart = Doughnut::make()
            ->title('任务状态分布')
            ->data($taskStats);
            
        return Card::make('任务进度', $chart);
    }
    
    /**
     * 构建Agent状态
     */
    private function buildAgentStatus(User $user): Card
    {
        $agents = $user->agents()->with(['subTasks'])->get();
        
        return Card::make('我的Agent', view('user-admin.dashboard.agent-status', compact('agents')));
    }
    
    /**
     * 构建最近活动
     */
    private function buildRecentActivity(User $user): Card
    {
        $activities = $this->getRecentActivities($user);
        
        return Card::make('最近活动', view('user-admin.dashboard.recent-activity', compact('activities')));
    }
}
```

### 2. ProjectController - 项目管理

```php
<?php

namespace App\Modules\UserAdmin\Controllers;

class ProjectController extends AdminController
{
    protected $title = '我的项目';
    
    protected function grid(): Grid
    {
        $user = auth()->user();
        
        return Grid::make(Project::where('user_id', $user->id)->with(['tasks', 'repositories']), function (Grid $grid) {
            $grid->column('name', '项目名称')->link(function () {
                return "/user-admin/projects/{$this->id}";
            });
            
            $grid->column('description', '描述')->limit(50);
            
            $grid->column('status', '状态')->using([
                'active' => '活跃',
                'inactive' => '非活跃',
                'archived' => '已归档',
            ])->label([
                'active' => 'success',
                'inactive' => 'warning',
                'archived' => 'secondary',
            ]);
            
            $grid->column('tasks_count', '任务数量')->display(function () {
                return $this->tasks->count();
            });
            
            $grid->column('progress', '进度')->display(function () {
                return $this->getProgressPercentage() . '%';
            })->progressBar();
            
            $grid->column('repositories_count', '仓库数量')->display(function () {
                return $this->repositories->count();
            });
            
            $grid->column('created_at', '创建时间');
            
            // 筛选器
            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('status', '状态')->select([
                    'active' => '活跃',
                    'inactive' => '非活跃',
                    'archived' => '已归档',
                ]);
            });
            
            // 工具栏
            $grid->tools(function (Grid\Tools $tools) {
                $tools->append(new CreateProjectAction());
            });
            
            // 行操作
            $grid->actions(function (Grid\Displayers\Actions $actions) {
                $actions->add(new ViewProjectDetailAction());
                $actions->add(new ManageProjectMembersAction());
                $actions->add(new ProjectSettingsAction());
                
                if ($this->status !== 'archived') {
                    $actions->add(new ArchiveProjectAction());
                }
            });
        });
    }
    
    protected function form(): Form
    {
        return Form::make(Project::class, function (Form $form) {
            $form->hidden('user_id')->value(auth()->id());
            
            $form->text('name', '项目名称')->required()->help('项目的唯一标识名称');
            $form->textarea('description', '项目描述')->rows(4);
            
            $form->select('status', '状态')->options([
                'active' => '活跃',
                'inactive' => '非活跃',
            ])->default('active');
            
            $form->select('timezone', '时区')->options(
                collect(timezone_identifiers_list())->mapWithKeys(function ($tz) {
                    return [$tz => $tz];
                })
            )->default(config('app.timezone'));
            
            $form->divider();
            
            // 项目设置
            $form->switch('settings.auto_assign_tasks', '自动分配任务')->default(0);
            $form->switch('settings.github_sync_enabled', '启用GitHub同步')->default(1);
            $form->switch('settings.notification_enabled', '启用通知')->default(1);
            
            $form->number('settings.max_tasks', '最大任务数')->default(100)->min(1);
            $form->number('settings.max_members', '最大成员数')->default(10)->min(1);
            
            // 保存后回调
            $form->saved(function (Form $form) {
                // 创建默认任务
                if ($form->isCreating()) {
                    event(new ProjectCreated($form->model(), auth()->user()));
                }
            });
        });
    }
    
    protected function detail($id): Show
    {
        return Show::make($id, Project::with(['tasks', 'repositories', 'members']), function (Show $show) {
            $show->field('name', '项目名称');
            $show->field('description', '项目描述');
            $show->field('status', '状态')->using([
                'active' => '活跃',
                'inactive' => '非活跃',
                'archived' => '已归档',
            ]);
            
            $show->field('progress', '完成进度')->as(function () {
                return $this->getProgressPercentage() . '%';
            });
            
            $show->divider();
            
            // 任务列表
            $show->relation('tasks', '项目任务', function ($model) {
                $grid = new Grid($model);
                $grid->column('title', '任务标题');
                $grid->column('status', '状态')->label();
                $grid->column('priority', '优先级')->label();
                $grid->column('completion_percentage', '完成度')->progressBar();
                $grid->column('assignee.name', '负责人');
                $grid->column('due_date', '截止时间');
                return $grid;
            });
            
            // 仓库列表
            $show->relation('repositories', '关联仓库', function ($model) {
                $grid = new Grid($model);
                $grid->column('repository_name', '仓库名称');
                $grid->column('repository_url', '仓库地址')->link();
                $grid->column('provider', '提供商');
                $grid->column('sync_status', '同步状态')->label();
                $grid->column('last_sync_at', '最后同步');
                return $grid;
            });
        });
    }
}
```

### 3. AgentController - Agent管理

```php
<?php

namespace App\Modules\UserAdmin\Controllers;

class AgentController extends AdminController
{
    protected $title = '我的Agent';
    
    protected function grid(): Grid
    {
        $user = auth()->user();
        
        return Grid::make(Agent::where('user_id', $user->id)->with(['subTasks']), function (Grid $grid) {
            $grid->column('agent_id', 'Agent ID')->copyable();
            $grid->column('name', '名称');
            
            $grid->column('type', '类型')->using([
                'claude' => 'Claude',
                'gpt' => 'GPT',
                'custom' => '自定义',
            ])->label();
            
            $grid->column('status', '状态')->using([
                'active' => '活跃',
                'inactive' => '非活跃',
                'pending' => '待审核',
            ])->label([
                'active' => 'success',
                'inactive' => 'secondary',
                'pending' => 'warning',
            ]);
            
            $grid->column('allowed_projects', '授权项目')->display(function ($projects) {
                if (empty($projects)) return '无';
                
                $projectNames = Project::whereIn('id', $projects)->pluck('name');
                return $projectNames->implode(', ');
            });
            
            $grid->column('tasks_completed', '完成任务数')->display(function () {
                return $this->subTasks()->where('status', 'completed')->count();
            });
            
            $grid->column('last_active_at', '最后活跃');
            $grid->column('created_at', '创建时间');
            
            // 筛选器
            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('status', '状态')->select([
                    'active' => '活跃',
                    'inactive' => '非活跃',
                    'pending' => '待审核',
                ]);
                
                $filter->equal('type', '类型')->select([
                    'claude' => 'Claude',
                    'gpt' => 'GPT',
                    'custom' => '自定义',
                ]);
            });
            
            // 工具栏
            $grid->tools(function (Grid\Tools $tools) {
                $tools->append(new RegisterAgentAction());
            });
            
            // 行操作
            $grid->actions(function (Grid\Displayers\Actions $actions) {
                $actions->add(new ViewAgentDetailAction());
                $actions->add(new ManageAgentPermissionsAction());
                $actions->add(new RegenerateTokenAction());
                
                if ($this->status === 'active') {
                    $actions->add(new DeactivateAgentAction());
                } else {
                    $actions->add(new ActivateAgentAction());
                }
            });
        });
    }
    
    protected function form(): Form
    {
        return Form::make(Agent::class, function (Form $form) {
            $user = auth()->user();
            $form->hidden('user_id')->value($user->id);
            
            $form->text('name', 'Agent名称')->required()->help('为您的Agent起一个易识别的名称');
            
            $form->select('type', 'Agent类型')->options([
                'claude' => 'Claude',
                'gpt' => 'GPT',
                'custom' => '自定义',
            ])->required();
            
            $form->textarea('description', '描述')->rows(3)->help('描述这个Agent的用途和特点');
            
            $form->multipleSelect('allowed_projects', '授权项目')
                ->options($user->projects()->pluck('name', 'id'))
                ->help('选择此Agent可以访问的项目');
            
            $form->checkbox('allowed_actions', '允许操作')->options([
                'read' => '读取数据',
                'create_task' => '创建任务',
                'update_task' => '更新任务',
                'claim_task' => '认领任务',
                'complete_task' => '完成任务',
                'sync_github' => '同步GitHub',
            ])->default(['read', 'create_task', 'update_task']);
            
            $form->number('token_expires_in', '令牌有效期(小时)')
                ->default(24)
                ->min(1)
                ->max(720)
                ->help('Agent访问令牌的有效期，最长30天');
            
            // 保存后回调
            $form->saved(function (Form $form) {
                if ($form->isCreating()) {
                    // 生成访问令牌
                    $token = app(AgentService::class)->generateAccessToken($form->model()->agent_id);
                    
                    // 显示令牌给用户
                    admin_toastr("Agent创建成功！访问令牌：{$token}", 'success');
                }
            });
        });
    }
    
    protected function detail($id): Show
    {
        return Show::make($id, Agent::with(['subTasks', 'user']), function (Show $show) {
            $show->field('agent_id', 'Agent ID');
            $show->field('name', '名称');
            $show->field('type', '类型');
            $show->field('status', '状态');
            $show->field('description', '描述');
            
            $show->field('allowed_projects', '授权项目')->as(function ($projects) {
                if (empty($projects)) return '无';
                
                $projectNames = Project::whereIn('id', $projects)->pluck('name');
                return $projectNames->implode(', ');
            });
            
            $show->field('allowed_actions', '允许操作')->as(function ($actions) {
                return is_array($actions) ? implode(', ', $actions) : '无';
            });
            
            $show->field('last_active_at', '最后活跃');
            $show->field('created_at', '创建时间');
            
            $show->divider();
            
            // 子任务执行记录
            $show->relation('subTasks', '执行记录', function ($model) {
                $grid = new Grid($model);
                $grid->column('title', '任务标题');
                $grid->column('type', '类型');
                $grid->column('status', '状态')->label();
                $grid->column('started_at', '开始时间');
                $grid->column('completed_at', '完成时间');
                $grid->column('actual_duration', '执行时长')->display(function ($duration) {
                    return $duration ? gmdate('H:i:s', $duration) : '-';
                });
                return $grid;
            });
        });
    }
}
```

### 4. GitHubController - GitHub集成

```php
<?php

namespace App\Modules\UserAdmin\Controllers;

class GitHubController extends AdminController
{
    protected $title = 'GitHub集成';
    
    public function index(Content $content): Content
    {
        $user = auth()->user();
        $connections = $user->gitHubConnections ?? collect();
        
        return $content
            ->title('GitHub集成管理')
            ->description('管理您的GitHub账户连接和仓库')
            ->body($this->buildConnectionStatus($user))
            ->body($this->buildRepositoryList($user))
            ->body($this->buildSyncHistory($user));
    }
    
    /**
     * 连接GitHub账户
     */
    public function connect(): RedirectResponse
    {
        $state = Str::random(40);
        session(['github_state' => $state]);
        
        $query = http_build_query([
            'client_id' => config('services.github.client_id'),
            'redirect_uri' => route('user-admin.github.callback'),
            'scope' => 'repo,user:email',
            'state' => $state,
        ]);
        
        return redirect('https://github.com/login/oauth/authorize?' . $query);
    }
    
    /**
     * GitHub OAuth回调
     */
    public function callback(Request $request): RedirectResponse
    {
        if ($request->state !== session('github_state')) {
            return redirect()->route('user-admin.github.index')
                ->with('error', '状态验证失败');
        }
        
        try {
            $tokenResponse = Http::post('https://github.com/login/oauth/access_token', [
                'client_id' => config('services.github.client_id'),
                'client_secret' => config('services.github.client_secret'),
                'code' => $request->code,
            ])->json();
            
            if (!isset($tokenResponse['access_token'])) {
                throw new Exception('获取访问令牌失败');
            }
            
            // 获取用户信息
            $userResponse = Http::withToken($tokenResponse['access_token'])
                ->get('https://api.github.com/user')
                ->json();
            
            // 保存连接信息
            app(GitHubIntegrationService::class)->saveConnection(
                auth()->user(),
                $tokenResponse['access_token'],
                $userResponse
            );
            
            return redirect()->route('user-admin.github.index')
                ->with('success', 'GitHub账户连接成功');
                
        } catch (Exception $e) {
            return redirect()->route('user-admin.github.index')
                ->with('error', 'GitHub连接失败：' . $e->getMessage());
        }
    }
    
    /**
     * 构建连接状态
     */
    private function buildConnectionStatus(User $user): Card
    {
        $connection = $user->gitHubConnection;
        
        return Card::make('连接状态', view('user-admin.github.connection-status', compact('connection')));
    }
    
    /**
     * 构建仓库列表
     */
    private function buildRepositoryList(User $user): Card
    {
        if (!$user->gitHubConnection) {
            return Card::make('我的仓库', '<p>请先连接GitHub账户</p>');
        }
        
        $repositories = app(GitHubIntegrationService::class)->getUserRepositories($user);
        
        return Card::make('我的仓库', view('user-admin.github.repository-list', compact('repositories')));
    }
}
```

## 配置文件

### user-admin.php

```php
<?php

// config/user-admin.php
return [
    'name' => 'MCP Tools 用户后台',
    'logo' => '<b>MCP</b> Tools',
    'logo-mini' => '<b>MCP</b>',
    
    'route' => [
        'prefix' => env('USER_ADMIN_ROUTE_PREFIX', 'user-admin'),
        'namespace' => 'App\\Modules\\UserAdmin\\Controllers',
        'middleware' => ['web', 'user-admin.auth'],
    ],
    
    'auth' => [
        'guards' => [
            'user-admin' => [
                'driver' => 'session',
                'provider' => 'users',
            ],
        ],
        'providers' => [
            'users' => [
                'driver' => 'eloquent',
                'model' => App\Modules\User\Models\User::class,
            ],
        ],
    ],
    
    'limits' => [
        'max_projects' => env('USER_MAX_PROJECTS', 10),
        'max_agents' => env('USER_MAX_AGENTS', 5),
        'max_repositories' => env('USER_MAX_REPOSITORIES', 20),
        'max_tasks_per_project' => env('USER_MAX_TASKS_PER_PROJECT', 100),
    ],
    
    'features' => [
        'project_creation' => env('USER_CAN_CREATE_PROJECTS', true),
        'agent_registration' => env('USER_CAN_REGISTER_AGENTS', true),
        'github_integration' => env('USER_GITHUB_INTEGRATION', true),
        'data_export' => env('USER_DATA_EXPORT', true),
    ],
    
    'github' => [
        'scopes' => ['repo', 'user:email'],
        'webhook_events' => ['push', 'pull_request', 'issues'],
    ],
];
```

---

**相关文档**：
- [超级管理员模块](./super-admin.md)
- [项目模块](./project.md)
- [任务模块](./task.md)
- [Agent模块](./agent.md)
