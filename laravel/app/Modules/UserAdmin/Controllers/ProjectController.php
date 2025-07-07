<?php

namespace App\Modules\UserAdmin\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use App\Http\Controllers\Controller;
use App\Modules\Project\Models\Project;

class ProjectController extends Controller
{
    /**
     * 显示用户的项目列表
     */
    public function index(Request $request): View
    {
        $user = auth()->user();
        
        $projects = Project::where('user_id', $user->id)
            ->orderBy('updated_at', 'desc')
            ->paginate(15);
        
        return view('user-admin.projects.index', compact('projects'));
    }

    /**
     * 显示创建项目表单
     */
    public function create(Request $request): View
    {
        return view('user-admin.projects.create');
    }

    /**
     * 存储新项目
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:active,completed,on_hold,cancelled',
            'priority' => 'required|in:low,medium,high,urgent',
        ]);

        $user = auth()->user();
        
        $project = Project::create([
            'user_id' => $user->id,
            'name' => $validated['name'],
            'description' => $validated['description'],
            'status' => $validated['status'],
            'priority' => $validated['priority'],
        ]);

        return redirect()->route('user-admin.projects.show', $project)
            ->with('success', '项目创建成功！');
    }

    /**
     * 显示指定项目
     */
    public function show(Request $request, Project $project): View
    {
        $this->authorize('view', $project);
        
        return view('user-admin.projects.show', compact('project'));
    }

    /**
     * 显示编辑项目表单
     */
    public function edit(Request $request, Project $project): View
    {
        $this->authorize('update', $project);
        
        return view('user-admin.projects.edit', compact('project'));
    }

    /**
     * 更新指定项目
     */
    public function update(Request $request, Project $project): RedirectResponse
    {
        $this->authorize('update', $project);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:active,completed,on_hold,cancelled',
            'priority' => 'required|in:low,medium,high,urgent',
        ]);

        $project->update($validated);

        return redirect()->route('user-admin.projects.show', $project)
            ->with('success', '项目更新成功！');
    }

    /**
     * 删除指定项目
     */
    public function destroy(Request $request, Project $project): RedirectResponse
    {
        $this->authorize('delete', $project);
        
        $project->delete();

        return redirect()->route('user-admin.projects.index')
            ->with('success', '项目删除成功！');
    }

    /**
     * 获取项目统计信息
     */
    public function stats(Request $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);
        
        $stats = [
            'total_tasks' => $project->tasks()->count(),
            'completed_tasks' => $project->tasks()->where('status', 'completed')->count(),
            'pending_tasks' => $project->tasks()->where('status', 'pending')->count(),
            'in_progress_tasks' => $project->tasks()->where('status', 'in_progress')->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }
}
