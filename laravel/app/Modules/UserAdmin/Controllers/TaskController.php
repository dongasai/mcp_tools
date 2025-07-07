<?php

namespace App\Modules\UserAdmin\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use App\Http\Controllers\Controller;
use App\Modules\Task\Models\Task;
use App\Modules\Project\Models\Project;

class TaskController extends Controller
{
    /**
     * 显示用户的任务列表
     */
    public function index(Request $request): View
    {
        $user = auth()->user();
        
        $query = Task::where('user_id', $user->id);
        
        // 筛选条件
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }
        
        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
        }
        
        $tasks = $query->with(['project', 'parentTask'])
            ->orderBy('priority', 'desc')
            ->orderBy('updated_at', 'desc')
            ->paginate(15);
        
        $projects = Project::where('user_id', $user->id)->get();
        
        return view('user-admin.tasks.index', compact('tasks', 'projects'));
    }

    /**
     * 显示创建任务表单
     */
    public function create(Request $request): View
    {
        $user = auth()->user();
        $projects = Project::where('user_id', $user->id)->get();
        $parentTasks = Task::where('user_id', $user->id)
            ->where('type', 'main')
            ->get();
        
        return view('user-admin.tasks.create', compact('projects', 'parentTasks'));
    }

    /**
     * 存储新任务
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:main,sub,milestone,bug,feature,improvement',
            'status' => 'required|in:pending,in_progress,completed,blocked,cancelled,on_hold',
            'priority' => 'required|in:low,medium,high,urgent',
            'project_id' => 'nullable|exists:projects,id',
            'parent_task_id' => 'nullable|exists:tasks,id',
            'estimated_hours' => 'nullable|numeric|min:0',
            'due_date' => 'nullable|date',
        ]);

        $user = auth()->user();
        
        $task = Task::create([
            'user_id' => $user->id,
            'title' => $validated['title'],
            'description' => $validated['description'],
            'type' => $validated['type'],
            'status' => $validated['status'],
            'priority' => $validated['priority'],
            'project_id' => $validated['project_id'],
            'parent_task_id' => $validated['parent_task_id'],
            'estimated_hours' => $validated['estimated_hours'],
            'due_date' => $validated['due_date'],
        ]);

        return redirect()->route('user-admin.tasks.show', $task)
            ->with('success', '任务创建成功！');
    }

    /**
     * 显示指定任务
     */
    public function show(Request $request, Task $task): View
    {
        $this->authorize('view', $task);
        
        $task->load(['project', 'parentTask', 'subTasks']);
        
        return view('user-admin.tasks.show', compact('task'));
    }

    /**
     * 显示编辑任务表单
     */
    public function edit(Request $request, Task $task): View
    {
        $this->authorize('update', $task);
        
        $user = auth()->user();
        $projects = Project::where('user_id', $user->id)->get();
        $parentTasks = Task::where('user_id', $user->id)
            ->where('type', 'main')
            ->where('id', '!=', $task->id)
            ->get();
        
        return view('user-admin.tasks.edit', compact('task', 'projects', 'parentTasks'));
    }

    /**
     * 更新指定任务
     */
    public function update(Request $request, Task $task): RedirectResponse
    {
        $this->authorize('update', $task);
        
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:main,sub,milestone,bug,feature,improvement',
            'status' => 'required|in:pending,in_progress,completed,blocked,cancelled,on_hold',
            'priority' => 'required|in:low,medium,high,urgent',
            'project_id' => 'nullable|exists:projects,id',
            'parent_task_id' => 'nullable|exists:tasks,id',
            'estimated_hours' => 'nullable|numeric|min:0',
            'actual_hours' => 'nullable|numeric|min:0',
            'progress' => 'nullable|integer|min:0|max:100',
            'due_date' => 'nullable|date',
        ]);

        $task->update($validated);

        return redirect()->route('user-admin.tasks.show', $task)
            ->with('success', '任务更新成功！');
    }

    /**
     * 删除指定任务
     */
    public function destroy(Request $request, Task $task): RedirectResponse
    {
        $this->authorize('delete', $task);
        
        $task->delete();

        return redirect()->route('user-admin.tasks.index')
            ->with('success', '任务删除成功！');
    }

    /**
     * 开始任务
     */
    public function start(Request $request, Task $task): RedirectResponse
    {
        $this->authorize('update', $task);
        
        $task->update(['status' => 'in_progress']);

        return redirect()->back()->with('success', '任务已开始！');
    }

    /**
     * 完成任务
     */
    public function complete(Request $request, Task $task): RedirectResponse
    {
        $this->authorize('update', $task);
        
        $task->update([
            'status' => 'completed',
            'progress' => 100,
        ]);

        return redirect()->back()->with('success', '任务已完成！');
    }

    /**
     * 暂停任务
     */
    public function pause(Request $request, Task $task): RedirectResponse
    {
        $this->authorize('update', $task);
        
        $task->update(['status' => 'on_hold']);

        return redirect()->back()->with('success', '任务已暂停！');
    }
}
