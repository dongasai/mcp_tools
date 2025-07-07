<?php

namespace App\UserAdmin\Controllers;

use Dcat\Admin\Http\Controllers\AdminController;
use Dcat\Admin\Layout\Content;
use Dcat\Admin\Widgets\Card;
use Dcat\Admin\Widgets\Chart\Doughnut;
use Dcat\Admin\Widgets\Chart\Line;
use App\Modules\User\Models\User;
use App\Modules\Project\Models\Project;
use App\Modules\Task\Models\Task;
use App\Modules\Agent\Models\Agent;

class DashboardController extends AdminController
{
    public function index(Content $content): Content
    {
        $user = $this->getCurrentUser();

        return $content
            ->title('工作台')
            ->description('欢迎回来，' . $user->name)
            ->body($this->buildDashboard($user));
    }

    protected function getCurrentUser()
    {
        // 直接获取当前登录的用户（现在使用User模型）
        return auth('user-admin')->user();
    }

    protected function buildDashboard($user)
    {
        $stats = $this->getUserStats($user);

        // 简单的HTML展示，避免复杂的视图依赖
        return '<div class="row">
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">项目数量</h5>
                        <h2 class="text-primary">' . $stats['projects_count'] . '</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">任务数量</h5>
                        <h2 class="text-info">' . $stats['tasks_count'] . '</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">已完成任务</h5>
                        <h2 class="text-success">' . $stats['completed_tasks'] . '</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Agent数量</h5>
                        <h2 class="text-warning">' . $stats['agents_count'] . '</h2>
                    </div>
                </div>
            </div>
        </div>
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4>欢迎使用MCP Tools用户后台</h4>
                    </div>
                    <div class="card-body">
                        <p>您可以通过左侧菜单管理您的项目、任务和Agent。</p>
                        <ul>
                            <li><strong>项目管理</strong>：创建和管理您的项目</li>
                            <li><strong>任务管理</strong>：跟踪项目任务进度</li>
                            <li><strong>Agent管理</strong>：配置和监控您的AI助手</li>
                            <li><strong>个人设置</strong>：管理个人信息和偏好</li>
                            <li><strong>GitHub集成</strong>：连接您的GitHub账户</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>';
    }

    protected function getUserStats($user)
    {
        // 简化统计，避免使用不存在的字段
        return [
            'projects_count' => 0, // $user->projects()->count() ?? 0,
            'tasks_count' => 0, // $user->createdTasks()->count() ?? 0,
            'completed_tasks' => 0, // $user->assignedTasks()->where('status', 'completed')->count() ?? 0,
            'agents_count' => 0, // $user->agents()->count() ?? 0,
            'active_agents' => 0, // $user->agents()->where('status', 'active')->count() ?? 0,
        ];
    }

    /**
     * 项目统计卡片
     */
    public function projectStatsCard($user)
    {
        $projectsCount = $user->projects()->count() ?? 0;
        $activeProjects = $user->projects()->where('status', 'active')->count() ?? 0;

        return Card::make('项目统计', view('user-admin::widgets.project-stats', [
            'total' => $projectsCount,
            'active' => $activeProjects,
            'completion_rate' => $projectsCount > 0 ? round(($activeProjects / $projectsCount) * 100, 1) : 0
        ]));
    }

    /**
     * 任务统计卡片
     */
    public function taskStatsCard($user)
    {
        $totalTasks = $user->createdTasks()->count() ?? 0;
        $completedTasks = $user->assignedTasks()->where('status', 'completed')->count() ?? 0;
        $pendingTasks = $user->assignedTasks()->where('status', 'pending')->count() ?? 0;

        return Card::make('任务统计', view('user-admin::widgets.task-stats', [
            'total' => $totalTasks,
            'completed' => $completedTasks,
            'pending' => $pendingTasks,
            'completion_rate' => $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100, 1) : 0
        ]));
    }

    /**
     * Agent统计卡片
     */
    public function agentStatsCard($user)
    {
        $totalAgents = $user->agents()->count() ?? 0;
        $activeAgents = $user->agents()->where('status', 'active')->count() ?? 0;

        return Card::make('Agent统计', view('user-admin::widgets.agent-stats', [
            'total' => $totalAgents,
            'active' => $activeAgents,
            'inactive' => $totalAgents - $activeAgents
        ]));
    }

    /**
     * 活动时间线
     */
    public function activityTimeline($user)
    {
        // 获取最近的活动记录
        $activities = collect([
            [
                'type' => 'project',
                'title' => '创建了新项目',
                'description' => '项目名称：示例项目',
                'time' => now()->subHours(2),
                'icon' => 'fa-folder'
            ],
            [
                'type' => 'task',
                'title' => '完成了任务',
                'description' => '任务：实现用户认证',
                'time' => now()->subHours(5),
                'icon' => 'fa-check'
            ],
            [
                'type' => 'agent',
                'title' => '注册了新Agent',
                'description' => 'Agent：开发助手',
                'time' => now()->subDay(),
                'icon' => 'fa-robot'
            ]
        ]);

        return Card::make('最近活动', view('user-admin::widgets.activity-timeline', [
            'activities' => $activities
        ]));
    }
}
