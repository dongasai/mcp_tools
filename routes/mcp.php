<?php

use PhpMcp\Laravel\Facades\Mcp;
use App\Modules\Mcp\Tools\TaskTool;
use App\Modules\Mcp\Tools\ProjectTool;
use App\Modules\Mcp\Tools\AgentTool;
use App\Modules\Mcp\Tools\AskQuestionTool;
use App\Modules\Mcp\Tools\CheckAnswerTool;
use App\Modules\Mcp\Tools\GetQuestionsTool;
use App\Modules\Mcp\Tools\QuestionBatchTool;
use App\Modules\Mcp\Resources\ProjectResource;
use App\Modules\Mcp\Resources\TaskResource;

/*
|--------------------------------------------------------------------------
| MCP Tools Registration
|--------------------------------------------------------------------------
|
| Register MCP tools that can be called by AI agents
|
*/

// Task Management Tools
Mcp::tool('create_main_task', [TaskTool::class, 'createMainTask'])
    ->description('Create a new main task in a project');

Mcp::tool('create_sub_task', [TaskTool::class, 'createSubTask'])
    ->description('Create a sub-task under a parent task');

Mcp::tool('list_tasks', [TaskTool::class, 'listTasks'])
    ->description('List tasks with optional filters');

Mcp::tool('get_task', [TaskTool::class, 'getTask'])
    ->description('Get detailed information about a specific task');

Mcp::tool('complete_task', [TaskTool::class, 'completeTask'])
    ->description('Mark a task as completed');

Mcp::tool('add_comment', [TaskTool::class, 'addComment'])
    ->description('Add a comment to a task');

Mcp::tool('get_assigned_tasks', [TaskTool::class, 'getAssignedTasks'])
    ->description('Get tasks assigned to the current agent');

// Project Management Tools
Mcp::tool('project_manager', [ProjectTool::class, 'call'])
    ->description('Manage projects - create, update, and query project information');

// Agent Management Tools
Mcp::tool('agent_manager', [AgentTool::class, 'call'])
    ->description('Manage agents - create, update, and query agent information');

// Question/Answer Tools
Mcp::tool('ask_question', AskQuestionTool::class)
    ->description('Agent向用户提出问题，获取指导、确认或澄清');

Mcp::tool('check_answer', CheckAnswerTool::class)
    ->description('检查问题的答案状态');

Mcp::tool('get_questions', GetQuestionsTool::class)
    ->description('获取问题列表');

Mcp::tool('question_batch', QuestionBatchTool::class)
    ->description('批量处理问题');

/*
|--------------------------------------------------------------------------
| MCP Resources Registration
|--------------------------------------------------------------------------
|
| Register MCP resources that provide read-only access to data
|
*/

// Project Resources
Mcp::resourceTemplate('project://{path}', [ProjectResource::class, 'read'])
    ->name('project_resource')
    ->description('Access to project information')
    ->mimeType('application/json');

// Task Resources
Mcp::resourceTemplate('task://{path}', [TaskResource::class, 'read'])
    ->name('task_resource')
    ->description('Access to task information')
    ->mimeType('application/json');

// Static Resources - 需要创建对应的处理类
// Mcp::resource('server://info', [ServerInfoResource::class, 'read'])
//     ->name('server_info')
//     ->description('Server information and capabilities')
//     ->mimeType('application/json');

// Mcp::resource('server://status', [ServerStatusResource::class, 'read'])
//     ->name('server_status')
//     ->description('Server runtime status')
//     ->mimeType('application/json');
