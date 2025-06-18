# 包集成架构文档

## 概述

本文档详细说明了MCP Tools项目中各个第三方包的集成方式、使用场景和架构设计。

## 核心包集成架构

### 1. php-mcp/laravel - MCP协议核心

#### 包信息
- **版本**：最新稳定版
- **作用**：为Laravel提供原生MCP协议支持
- **官方文档**：[php-mcp/laravel](https://github.com/php-mcp/laravel)

#### 集成架构

```php
// config/mcp.php - MCP配置
return [
    'server' => [
        'name' => 'MCP Tools Server',
        'version' => '1.0.0',
        'transport' => 'sse', // Server-Sent Events
        'host' => env('MCP_HOST', 'localhost'),
        'port' => env('MCP_PORT', 34004),
    ],
    
    'capabilities' => [
        'resources' => true,
        'tools' => true,
        'notifications' => true,
    ],
    
    'resources' => [
        'task' => App\Modules\Mcp\Resources\TaskResource::class,
        'project' => App\Modules\Mcp\Resources\ProjectResource::class,
        'github' => App\Modules\Mcp\Resources\GitHubResource::class,
    ],
    
    'tools' => [
        'task_management' => App\Modules\Mcp\Tools\TaskManagementTool::class,
        'project_query' => App\Modules\Mcp\Tools\ProjectQueryTool::class,
        'github_sync' => App\Modules\Mcp\Tools\GitHubSyncTool::class,
    ],
];
```

#### MCP服务器实现

```php
<?php

namespace App\Modules\Mcp\Server;

use PhpMcp\Laravel\McpServer;
use PhpMcp\Laravel\Contracts\ResourceInterface;
use PhpMcp\Laravel\Contracts\ToolInterface;

class CustomMcpServer extends McpServer
{
    /**
     * 注册MCP资源
     */
    protected function registerResources(): void
    {
        $this->addResource('task', new TaskResource());
        $this->addResource('project', new ProjectResource());
        $this->addResource('github', new GitHubResource());
    }
    
    /**
     * 注册MCP工具
     */
    protected function registerTools(): void
    {
        $this->addTool('task_management', new TaskManagementTool());
        $this->addTool('project_query', new ProjectQueryTool());
        $this->addTool('github_sync', new GitHubSyncTool());
    }
    
    /**
     * 处理Agent认证
     */
    protected function authenticateAgent(string $token): ?Agent
    {
        return app(AgentService::class)->validateToken($token);
    }
}
```

#### MCP资源实现

```php
<?php

namespace App\Modules\Mcp\Resources;

use PhpMcp\Laravel\Contracts\ResourceInterface;
use PhpMcp\Laravel\Resources\Resource;

class TaskResource extends Resource implements ResourceInterface
{
    public function getUriPattern(): string
    {
        return 'task://';
    }
    
    public function read(string $uri, array $params = []): array
    {
        $taskId = $this->extractIdFromUri($uri);
        
        if ($taskId) {
            return $this->getTaskDetails($taskId);
        }
        
        return $this->listTasks($params);
    }
    
    private function getTaskDetails(int $taskId): array
    {
        $task = Task::with(['subTasks', 'project'])->findOrFail($taskId);
        
        return [
            'id' => $task->id,
            'title' => $task->title,
            'description' => $task->description,
            'status' => $task->status,
            'progress' => $task->completion_percentage,
            'sub_tasks' => $task->subTasks->map(function ($subTask) {
                return [
                    'id' => $subTask->id,
                    'title' => $subTask->title,
                    'status' => $subTask->status,
                    'type' => $subTask->type,
                ];
            })->toArray(),
        ];
    }
}
```

### 2. dcat/laravel-admin - 后台管理

#### 包信息
- **版本**：2.0.x-dev
- **作用**：快速构建后台管理界面
- **官方文档**：[Dcat Admin](https://dcatadmin.com/)

#### 集成架构

```php
// config/admin.php - Admin配置
return [
    'name' => 'MCP Tools Admin',
    'logo' => '<b>MCP</b> Tools',
    'logo-mini' => '<b>MCP</b>',
    
    'route' => [
        'prefix' => env('ADMIN_ROUTE_PREFIX', 'admin'),
        'namespace' => 'App\\Modules\\Admin\\Controllers',
        'middleware' => ['web', 'admin'],
    ],
    
    'auth' => [
        'guards' => [
            'admin' => [
                'driver' => 'session',
                'provider' => 'admin',
            ],
        ],
        'providers' => [
            'admin' => [
                'driver' => 'eloquent',
                'model' => App\Modules\User\Models\User::class,
            ],
        ],
    ],
];
```

#### Admin控制器实现

```php
<?php

namespace App\Modules\Admin\Controllers;

use Dcat\Admin\Controllers\AdminController;
use Dcat\Admin\Grid;
use Dcat\Admin\Form;
use Dcat\Admin\Show;

class TaskController extends AdminController
{
    protected $title = '任务管理';
    
    protected function grid(): Grid
    {
        return Grid::make(Task::with(['project', 'creator', 'assignee']), function (Grid $grid) {
            $grid->column('id', 'ID')->sortable();
            $grid->column('title', '标题')->limit(30);
            $grid->column('status', '状态')->using([
                'pending' => '待处理',
                'in_progress' => '进行中',
                'completed' => '已完成',
                'cancelled' => '已取消',
            ])->label([
                'pending' => 'warning',
                'in_progress' => 'primary',
                'completed' => 'success',
                'cancelled' => 'danger',
            ]);
            $grid->column('priority', '优先级')->using([
                'low' => '低',
                'medium' => '中',
                'high' => '高',
                'urgent' => '紧急',
            ]);
            $grid->column('completion_percentage', '完成度')->progressBar();
            $grid->column('project.name', '项目');
            $grid->column('creator.name', '创建者');
            $grid->column('assignee.name', '负责人');
            $grid->column('created_at', '创建时间');
            
            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('status', '状态')->select([
                    'pending' => '待处理',
                    'in_progress' => '进行中',
                    'completed' => '已完成',
                ]);
                $filter->equal('priority', '优先级')->select([
                    'low' => '低',
                    'medium' => '中',
                    'high' => '高',
                    'urgent' => '紧急',
                ]);
                $filter->equal('project_id', '项目')->select(
                    Project::pluck('name', 'id')
                );
            });
        });
    }
    
    protected function form(): Form
    {
        return Form::make(Task::class, function (Form $form) {
            $form->text('title', '标题')->required();
            $form->textarea('description', '描述');
            $form->select('status', '状态')->options([
                'pending' => '待处理',
                'in_progress' => '进行中',
                'completed' => '已完成',
                'cancelled' => '已取消',
            ])->default('pending');
            $form->select('priority', '优先级')->options([
                'low' => '低',
                'medium' => '中',
                'high' => '高',
                'urgent' => '紧急',
            ])->default('medium');
            $form->select('project_id', '项目')->options(
                Project::pluck('name', 'id')
            )->required();
            $form->select('assigned_to', '负责人')->options(
                User::pluck('name', 'id')
            );
            $form->datetime('due_date', '截止时间');
            $form->number('estimated_hours', '预估工时');
        });
    }
    
    protected function detail($id): Show
    {
        return Show::make($id, Task::with(['subTasks', 'project', 'creator', 'assignee']), function (Show $show) {
            $show->field('title', '标题');
            $show->field('description', '描述');
            $show->field('status', '状态');
            $show->field('priority', '优先级');
            $show->field('completion_percentage', '完成度')->as(function ($value) {
                return $value . '%';
            });
            $show->field('project.name', '项目');
            $show->field('creator.name', '创建者');
            $show->field('assignee.name', '负责人');
            
            $show->divider();
            
            $show->relation('subTasks', '子任务', function ($model) {
                $grid = new Grid($model);
                $grid->column('title', '标题');
                $grid->column('status', '状态');
                $grid->column('type', '类型');
                $grid->column('agent.name', 'Agent');
                $grid->column('created_at', '创建时间');
                return $grid;
            });
        });
    }
}
```

### 3. inhere/php-validate - 数据验证

#### 包信息
- **版本**：^3.0
- **作用**：强大的PHP数据验证库
- **官方文档**：[php-validate](https://github.com/inhere/php-validate)

#### 集成架构

```php
<?php

namespace App\Modules\Core\Services;

use Inhere\Validate\Validation;

class ValidationService
{
    /**
     * 验证MCP消息格式
     */
    public function validateMcpMessage(array $data): array
    {
        $v = Validation::make($data, [
            'jsonrpc' => 'required|string|in:2.0',
            'method' => 'required|string',
            'params' => 'array',
            'id' => 'required|int|string',
        ]);
        
        if (!$v->validate()) {
            throw new ValidationException('Invalid MCP message format', $v->getErrors());
        }
        
        return $v->getSafeData();
    }
    
    /**
     * 验证任务创建数据
     */
    public function validateTaskCreation(array $data): array
    {
        $v = Validation::make($data, [
            'title' => 'required|string|minLen:3|maxLen:255',
            'description' => 'string|maxLen:1000',
            'priority' => 'string|in:low,medium,high,urgent',
            'project_id' => 'required|int|min:1',
            'assigned_to' => 'int|min:1',
            'due_date' => 'date',
            'estimated_hours' => 'float|min:0',
        ]);
        
        $v->addMessages([
            'title.required' => '任务标题不能为空',
            'title.minLen' => '任务标题至少3个字符',
            'project_id.required' => '必须选择项目',
        ]);
        
        if (!$v->validate()) {
            throw new ValidationException('Task validation failed', $v->getErrors());
        }
        
        return $v->getSafeData();
    }
    
    /**
     * 验证子任务数据
     */
    public function validateSubTaskCreation(array $data): array
    {
        $v = Validation::make($data, [
            'title' => 'required|string|minLen:3|maxLen:255',
            'description' => 'string|maxLen:500',
            'type' => 'required|string|in:code_analysis,file_operation,api_call,data_processing,github_operation,validation',
            'execution_data' => 'array',
            'estimated_duration' => 'int|min:1',
            'max_retries' => 'int|min:0|max:10',
        ]);
        
        if (!$v->validate()) {
            throw new ValidationException('SubTask validation failed', $v->getErrors());
        }
        
        return $v->getSafeData();
    }
    
    /**
     * 验证Agent注册数据
     */
    public function validateAgentRegistration(array $data): array
    {
        $v = Validation::make($data, [
            'name' => 'required|string|minLen:3|maxLen:100',
            'type' => 'required|string|in:claude,gpt,custom',
            'user_id' => 'required|int|min:1',
            'allowed_projects' => 'array',
            'allowed_projects.*' => 'int|min:1',
            'allowed_actions' => 'array',
            'allowed_actions.*' => 'string',
            'token_expires_in' => 'int|min:3600|max:2592000', // 1小时到30天
        ]);
        
        if (!$v->validate()) {
            throw new ValidationException('Agent validation failed', $v->getErrors());
        }
        
        return $v->getSafeData();
    }
}
```

### 4. spatie/laravel-route-attributes - 路由属性

#### 包信息
- **版本**：^1.25
- **作用**：使用PHP属性定义路由
- **官方文档**：[laravel-route-attributes](https://github.com/spatie/laravel-route-attributes)

#### 集成架构

```php
<?php

namespace App\Modules\Task\Controllers;

use Spatie\RouteAttributes\Attributes\Route;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Middleware;

#[Prefix('api/tasks')]
#[Middleware(['api', 'auth:sanctum'])]
class TaskController extends Controller
{
    #[Route('GET', '/', name: 'tasks.index')]
    public function index(Request $request): JsonResponse
    {
        $tasks = app(TaskService::class)->getUserTasks(
            $request->user(),
            $request->only(['status', 'project_id', 'priority'])
        );
        
        return TaskResource::collection($tasks)->response();
    }
    
    #[Route('POST', '/', name: 'tasks.store')]
    public function store(CreateTaskRequest $request): JsonResponse
    {
        $validatedData = app(ValidationService::class)
            ->validateTaskCreation($request->all());
            
        $task = app(TaskService::class)->create($validatedData, $request->user());
        
        return new TaskResource($task);
    }
    
    #[Route('GET', '/{task}', name: 'tasks.show')]
    public function show(Task $task): JsonResponse
    {
        $this->authorize('view', $task);
        
        $taskWithSubTasks = app(TaskService::class)->getTaskWithSubTasks($task->id);
        
        return new TaskResource($taskWithSubTasks);
    }
    
    #[Route('PUT', '/{task}', name: 'tasks.update')]
    public function update(UpdateTaskRequest $request, Task $task): JsonResponse
    {
        $this->authorize('update', $task);
        
        $validatedData = app(ValidationService::class)
            ->validateTaskCreation($request->all());
            
        $task = app(TaskService::class)->update($task, $validatedData);
        
        return new TaskResource($task);
    }
    
    #[Route('POST', '/{task}/complete', name: 'tasks.complete')]
    public function complete(Task $task): JsonResponse
    {
        $this->authorize('complete', $task);
        
        $task = app(TaskService::class)->complete($task);
        
        return new TaskResource($task);
    }
    
    #[Route('POST', '/{task}/claim', name: 'tasks.claim')]
    #[Middleware(['agent.auth'])]
    public function claim(Task $task): JsonResponse
    {
        $agent = request()->agent();
        
        $task = app(TaskService::class)->claim($task, $agent);
        
        return new TaskResource($task);
    }
}
```

#### 子任务控制器

```php
<?php

namespace App\Modules\Task\Controllers;

use Spatie\RouteAttributes\Attributes\Route;
use Spatie\RouteAttributes\Attributes\Prefix;
use Spatie\RouteAttributes\Attributes\Middleware;

#[Prefix('api/tasks/{task}/subtasks')]
#[Middleware(['api', 'agent.auth'])]
class SubTaskController extends Controller
{
    #[Route('POST', '/', name: 'subtasks.store')]
    public function store(CreateSubTaskRequest $request, Task $task): JsonResponse
    {
        $agent = $request->agent();
        
        $validatedData = app(ValidationService::class)
            ->validateSubTaskCreation($request->all());
            
        $subTask = app(SubTaskService::class)->createForTask(
            $task,
            $validatedData,
            $agent
        );
        
        return new SubTaskResource($subTask);
    }
    
    #[Route('POST', '/batch', name: 'subtasks.batch')]
    public function batchStore(Request $request, Task $task): JsonResponse
    {
        $agent = $request->agent();
        
        $subTasks = app(SubTaskService::class)->createBatch(
            $task,
            $request->sub_tasks,
            $agent
        );
        
        return SubTaskResource::collection($subTasks)->response();
    }
}

#[Prefix('api/subtasks')]
#[Middleware(['api', 'agent.auth'])]
class SubTaskActionController extends Controller
{
    #[Route('POST', '/{subTask}/start', name: 'subtasks.start')]
    public function start(SubTask $subTask): JsonResponse
    {
        $this->authorize('start', $subTask);
        
        $subTask = app(SubTaskService::class)->start($subTask);
        
        return new SubTaskResource($subTask);
    }
    
    #[Route('POST', '/{subTask}/complete', name: 'subtasks.complete')]
    public function complete(CompleteSubTaskRequest $request, SubTask $subTask): JsonResponse
    {
        $this->authorize('complete', $subTask);
        
        $subTask = app(SubTaskService::class)->complete(
            $subTask,
            $request->validated()['result_data'] ?? []
        );
        
        return new SubTaskResource($subTask);
    }
    
    #[Route('POST', '/{subTask}/fail', name: 'subtasks.fail')]
    public function fail(Request $request, SubTask $subTask): JsonResponse
    {
        $this->authorize('fail', $subTask);
        
        $subTask = app(SubTaskService::class)->fail(
            $subTask,
            $request->reason,
            $request->boolean('can_retry', true)
        );
        
        return new SubTaskResource($subTask);
    }
}
```

---

**相关文档**：
- [模块架构概述](../modules/README.md)
- [MCP协议模块](../modules/mcp.md)
- [任务模块](../modules/task.md)
