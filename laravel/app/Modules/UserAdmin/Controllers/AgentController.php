<?php

namespace App\Modules\UserAdmin\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use App\Http\Controllers\Controller;
use App\Modules\Agent\Models\Agent;

class AgentController extends Controller
{
    /**
     * 显示用户的Agent列表
     */
    public function index(Request $request): View
    {
        $user = auth()->user();
        
        $agents = Agent::where('user_id', $user->id)
            ->orderBy('updated_at', 'desc')
            ->paginate(15);
        
        return view('user-admin.agents.index', compact('agents'));
    }

    /**
     * 显示创建Agent表单
     */
    public function create(Request $request): View
    {
        return view('user-admin.agents.create');
    }

    /**
     * 存储新Agent
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|string|max:100',
            'capabilities' => 'nullable|array',
            'config' => 'nullable|array',
        ]);

        $user = auth()->user();
        
        $agent = Agent::create([
            'user_id' => $user->id,
            'name' => $validated['name'],
            'description' => $validated['description'],
            'type' => $validated['type'],
            'capabilities' => $validated['capabilities'] ?? [],
            'config' => $validated['config'] ?? [],
            'status' => 'inactive',
        ]);

        return redirect()->route('user-admin.agents.show', $agent)
            ->with('success', 'Agent创建成功！');
    }

    /**
     * 显示指定Agent
     */
    public function show(Request $request, Agent $agent): View
    {
        $this->authorize('view', $agent);
        
        return view('user-admin.agents.show', compact('agent'));
    }

    /**
     * 显示编辑Agent表单
     */
    public function edit(Request $request, Agent $agent): View
    {
        $this->authorize('update', $agent);
        
        return view('user-admin.agents.edit', compact('agent'));
    }

    /**
     * 更新指定Agent
     */
    public function update(Request $request, Agent $agent): RedirectResponse
    {
        $this->authorize('update', $agent);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|string|max:100',
            'capabilities' => 'nullable|array',
            'config' => 'nullable|array',
        ]);

        $agent->update([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'type' => $validated['type'],
            'capabilities' => $validated['capabilities'] ?? [],
            'config' => $validated['config'] ?? [],
        ]);

        return redirect()->route('user-admin.agents.show', $agent)
            ->with('success', 'Agent更新成功！');
    }

    /**
     * 删除指定Agent
     */
    public function destroy(Request $request, Agent $agent): RedirectResponse
    {
        $this->authorize('delete', $agent);
        
        $agent->delete();

        return redirect()->route('user-admin.agents.index')
            ->with('success', 'Agent删除成功！');
    }

    /**
     * 激活Agent
     */
    public function activate(Request $request, Agent $agent): RedirectResponse
    {
        $this->authorize('update', $agent);
        
        $agent->update(['status' => 'active']);

        return redirect()->back()->with('success', 'Agent已激活！');
    }

    /**
     * 停用Agent
     */
    public function deactivate(Request $request, Agent $agent): RedirectResponse
    {
        $this->authorize('update', $agent);
        
        $agent->update(['status' => 'inactive']);

        return redirect()->back()->with('success', 'Agent已停用！');
    }

    /**
     * 查看Agent日志
     */
    public function logs(Request $request, Agent $agent): View
    {
        $this->authorize('view', $agent);
        
        // TODO: 实现Agent日志查看功能
        $logs = [];
        
        return view('user-admin.agents.logs', compact('agent', 'logs'));
    }
}
