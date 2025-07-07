<?php

namespace App\Modules\UserAdmin\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use App\Http\Controllers\Controller;
use App\Modules\Project\Models\Project;
use App\Modules\Task\Models\Task;
use App\Modules\Agent\Models\Agent;

class DashboardController extends Controller
{
    /**
     * 用户仪表板首页
     */
    public function index(Request $request): View
    {
        $user = auth()->user();
        
        // 获取用户统计数据
        $stats = $this->getUserStats($user);
        
        // 获取最近的项目
        $recentProjects = Project::where('user_id', $user->id)
            ->orderBy('updated_at', 'desc')
            ->limit(5)
            ->get();
        
        // 获取待处理的任务
        $pendingTasks = Task::where('user_id', $user->id)
            ->where('status', 'pending')
            ->orderBy('priority', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
        
        // 获取进行中的任务
        $activeTasks = Task::where('user_id', $user->id)
            ->where('status', 'in_progress')
            ->orderBy('updated_at', 'desc')
            ->limit(5)
            ->get();
        
        // 获取用户的Agent
        $userAgents = Agent::where('user_id', $user->id)
            ->orderBy('updated_at', 'desc')
            ->limit(5)
            ->get();
        
        return view('user-admin.dashboard', compact(
            'user',
            'stats',
            'recentProjects',
            'pendingTasks',
            'activeTasks',
            'userAgents'
        ));
    }
    
    /**
     * 获取用户统计数据
     */
    protected function getUserStats($user): array
    {
        return [
            'total_projects' => Project::where('user_id', $user->id)->count(),
            'active_projects' => Project::where('user_id', $user->id)
                ->where('status', 'active')
                ->count(),
            'total_tasks' => Task::where('user_id', $user->id)->count(),
            'pending_tasks' => Task::where('user_id', $user->id)
                ->where('status', 'pending')
                ->count(),
            'in_progress_tasks' => Task::where('user_id', $user->id)
                ->where('status', 'in_progress')
                ->count(),
            'completed_tasks' => Task::where('user_id', $user->id)
                ->where('status', 'completed')
                ->count(),
            'total_agents' => Agent::where('user_id', $user->id)->count(),
            'active_agents' => Agent::where('user_id', $user->id)
                ->where('status', 'active')
                ->count(),
        ];
    }
    
    /**
     * 获取仪表板数据 (API)
     */
    public function data(Request $request): JsonResponse
    {
        $user = auth()->user();
        
        $data = [
            'stats' => $this->getUserStats($user),
            'recent_projects' => Project::where('user_id', $user->id)
                ->orderBy('updated_at', 'desc')
                ->limit(5)
                ->get(),
            'pending_tasks' => Task::where('user_id', $user->id)
                ->where('status', 'pending')
                ->orderBy('priority', 'desc')
                ->limit(10)
                ->get(),
            'active_tasks' => Task::where('user_id', $user->id)
                ->where('status', 'in_progress')
                ->limit(5)
                ->get(),
            'user_agents' => Agent::where('user_id', $user->id)
                ->orderBy('updated_at', 'desc')
                ->limit(5)
                ->get(),
        ];
        
        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
    
    /**
     * 获取任务进度统计
     */
    public function taskProgress(Request $request): JsonResponse
    {
        $user = auth()->user();
        
        $taskStats = [
            'pending' => Task::where('user_id', $user->id)->where('status', 'pending')->count(),
            'in_progress' => Task::where('user_id', $user->id)->where('status', 'in_progress')->count(),
            'completed' => Task::where('user_id', $user->id)->where('status', 'completed')->count(),
            'blocked' => Task::where('user_id', $user->id)->where('status', 'blocked')->count(),
            'cancelled' => Task::where('user_id', $user->id)->where('status', 'cancelled')->count(),
        ];
        
        return response()->json([
            'success' => true,
            'data' => $taskStats,
        ]);
    }
    
    /**
     * 获取项目统计
     */
    public function projectStats(Request $request): JsonResponse
    {
        $user = auth()->user();
        
        $projectStats = [
            'active' => Project::where('user_id', $user->id)->where('status', 'active')->count(),
            'completed' => Project::where('user_id', $user->id)->where('status', 'completed')->count(),
            'on_hold' => Project::where('user_id', $user->id)->where('status', 'on_hold')->count(),
            'cancelled' => Project::where('user_id', $user->id)->where('status', 'cancelled')->count(),
        ];
        
        return response()->json([
            'success' => true,
            'data' => $projectStats,
        ]);
    }
}
