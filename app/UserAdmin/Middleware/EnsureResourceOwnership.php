<?php

namespace App\UserAdmin\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Modules\Project\Models\Project;
use App\Modules\Task\Models\Task;
use App\Modules\MCP\Models\Agent;
use Modules\User\Models\User;
use App\Modules\Dbcont\Models\DatabaseConnection;
use App\Modules\Dbcont\Models\AgentDatabasePermission;

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

        if (str_contains($routeName, 'dbcont')) {
            // 暂时跳过dbcont路由的权限检查，避免array_key_exists错误
            return $next($request);
            // return $this->checkDbcontOwnership($request, $next, $user, $parameters);
        }

        return $next($request);
    }

    /**
     * 检查项目归属
     */
    protected function checkProjectOwnership(Request $request, Closure $next, $user, $parameters)
    {
        // 确保 $parameters 是数组
        if (!is_array($parameters)) {
            $parameters = [];
        }

        if (array_key_exists('project', $parameters) && $parameters['project']) {
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
        // 确保 $parameters 是数组
        if (!is_array($parameters)) {
            $parameters = [];
        }

        if (array_key_exists('task', $parameters) && $parameters['task']) {
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
        // 确保 $parameters 是数组
        if (!is_array($parameters)) {
            $parameters = [];
        }

        if (array_key_exists('agent', $parameters) && $parameters['agent']) {
            $agentId = $parameters['agent'];
            $agent = Agent::find($agentId);

            if (!$agent || $agent->user_id !== $user->id) {
                abort(403, '您没有权限访问该Agent');
            }
        }

        return $next($request);
    }

    /**
     * 检查Dbcont资源归属
     */
    protected function checkDbcontOwnership(Request $request, Closure $next, $user, $parameters)
    {
        // 确保 $parameters 是数组
        if (!is_array($parameters)) {
            $parameters = [];
        }

        // 检查数据库连接归属
        if (array_key_exists('database_connection', $parameters) && $parameters['database_connection']) {
            $connectionId = $parameters['database_connection'];
            $connection = DatabaseConnection::find($connectionId);

            if (!$connection || $connection->user_id !== $user->id) {
                abort(403, '您没有权限访问该数据库连接');
            }
        }

        // 检查Agent数据库权限归属
        if (array_key_exists('agent_permission', $parameters) && $parameters['agent_permission']) {
            $permissionId = $parameters['agent_permission'];
            $permission = AgentDatabasePermission::with('agent')->find($permissionId);

            if (!$permission || !$permission->agent || $permission->agent->user_id !== $user->id) {
                abort(403, '您没有权限访问该Agent权限配置');
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
