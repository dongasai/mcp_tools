<?php

namespace App\UserAdmin\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Modules\Project\Models\Project;
use App\Modules\Task\Models\Task;
use App\Modules\Agent\Models\Agent;
use App\Modules\User\Models\User;

class EnsureResourceOwnership
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $this->getCurrentUser();
        
        if (!$user) {
            return redirect()->route('login')->with('error', '请先登录');
        }

        // 检查路由参数中的资源ID
        $route = $request->route();
        $routeName = $route->getName();
        $parameters = $route->parameters();

        // 根据路由名称检查不同的资源
        if (str_contains($routeName, 'projects')) {
            return $this->checkProjectOwnership($request, $next, $user, $parameters);
        }

        if (str_contains($routeName, 'tasks')) {
            return $this->checkTaskOwnership($request, $next, $user, $parameters);
        }

        if (str_contains($routeName, 'agents')) {
            return $this->checkAgentOwnership($request, $next, $user, $parameters);
        }

        return $next($request);
    }

    /**
     * 检查项目归属
     */
    protected function checkProjectOwnership(Request $request, Closure $next, $user, $parameters)
    {
        if (isset($parameters['project'])) {
            $projectId = $parameters['project'];
            $project = Project::find($projectId);
            
            if (!$project || $project->user_id !== $user->id) {
                abort(403, '您没有权限访问该项目');
            }
        }

        return $next($request);
    }

    /**
     * 检查任务归属（通过项目归属）
     */
    protected function checkTaskOwnership(Request $request, Closure $next, $user, $parameters)
    {
        if (isset($parameters['task'])) {
            $taskId = $parameters['task'];
            $task = Task::with('project')->find($taskId);
            
            if (!$task || !$task->project || $task->project->user_id !== $user->id) {
                abort(403, '您没有权限访问该任务');
            }
        }

        return $next($request);
    }

    /**
     * 检查Agent归属
     */
    protected function checkAgentOwnership(Request $request, Closure $next, $user, $parameters)
    {
        if (isset($parameters['agent'])) {
            $agentId = $parameters['agent'];
            $agent = Agent::find($agentId);
            
            if (!$agent || $agent->user_id !== $user->id) {
                abort(403, '您没有权限访问该Agent');
            }
        }

        return $next($request);
    }

    /**
     * 获取当前用户
     */
    protected function getCurrentUser()
    {
        return auth('user-admin')->user();
    }
}
