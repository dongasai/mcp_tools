<?php

namespace App\UserAdmin\Controllers;

use Dcat\Admin\Http\Controllers\AdminController;
use Dcat\Admin\Layout\Content;
use Dcat\Admin\Widgets\Card;
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
        return auth('user-admin')->user();
    }

    protected function buildDashboard($user)
    {
        $stats = $this->getUserStats($user);

        return Card::make('用户后台仪表板',
            '<div class="row">
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title">项目数量</h5>
                            <h2 class="text-primary">' . $stats['projects_count'] . '</h2>
                            <p class="text-muted">我的项目</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title">任务数量</h5>
                            <h2 class="text-info">' . $stats['tasks_count'] . '</h2>
                            <p class="text-muted">总任务数</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title">进行中任务</h5>
                            <h2 class="text-warning">' . $stats['active_tasks_count'] . '</h2>
                            <p class="text-muted">活跃任务</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title">Agent数量</h5>
                            <h2 class="text-success">' . $stats['agents_count'] . '</h2>
                            <p class="text-muted">我的Agent</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="mt-4">
                <div class="card">
                    <div class="card-header">
                        <h4>欢迎使用MCP Tools用户后台</h4>
                    </div>
                    <div class="card-body">
                        <p>您好，' . $user->name . '！欢迎使用MCP Tools用户后台。</p>
                        <p>您可以通过左侧菜单管理您的项目、任务和Agent。</p>
                    </div>
                </div>
            </div>'
        );
    }

    protected function getUserStats($user)
    {
        return [
            'projects_count' => Project::where('user_id', $user->id)->count(),
            'tasks_count' => Task::whereHas('project', function($query) use ($user) {
                $query->where('user_id', $user->id);
            })->count(),
            'agents_count' => Agent::where('user_id', $user->id)->count(),
            'active_tasks_count' => Task::whereHas('project', function($query) use ($user) {
                $query->where('user_id', $user->id);
            })->whereIn('status', ['pending', 'in_progress'])->count(),
        ];
    }
}
