<?php

namespace App\Http\Controllers\UserAdmin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use App\Services\MemberService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class MemberController extends Controller
{
    protected MemberService $memberService;

    public function __construct(MemberService $memberService)
    {
        $this->memberService = $memberService;
        $this->middleware('auth');
    }

    /**
     * 显示项目成员列表
     */
    public function index(Project $project)
    {
        // 检查用户是否有权限查看项目成员
        $this->authorize('view', $project);

        $members = $this->memberService->getMembers($project);
        
        return view('user-admin.members.index', compact('project', 'members'));
    }

    /**
     * 显示添加成员表单
     */
    public function create(Project $project)
    {
        // 检查用户是否有权限管理项目成员
        $this->authorize('manageMember', $project);

        // 获取不是项目成员的用户列表
        $availableUsers = User::whereNotIn('id', function ($query) use ($project) {
            $query->select('user_id')
                  ->from('project_members')
                  ->where('project_id', $project->id);
        })->get();

        return view('user-admin.members.create', compact('project', 'availableUsers'));
    }

    /**
     * 添加项目成员
     */
    public function store(Request $request, Project $project): JsonResponse
    {
        // 检查用户是否有权限管理项目成员
        $this->authorize('manageMember', $project);

        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'role' => 'required|in:admin,member,viewer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => '验证失败',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $user = User::findOrFail($request->user_id);
            
            // 检查用户是否已经是项目成员
            if ($project->hasMember($user)) {
                return response()->json([
                    'success' => false,
                    'message' => '该用户已经是项目成员',
                ], 400);
            }

            $member = $this->memberService->addMember($project, $user, $request->role);

            return response()->json([
                'success' => true,
                'message' => '成员添加成功',
                'data' => [
                    'member' => $member->load('user'),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '添加成员失败: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 显示成员详情
     */
    public function show(Project $project, ProjectMember $member)
    {
        // 检查成员是否属于该项目
        if ($member->project_id !== $project->id) {
            abort(404);
        }

        // 检查用户是否有权限查看项目成员
        $this->authorize('view', $project);

        return view('user-admin.members.show', compact('project', 'member'));
    }

    /**
     * 显示编辑成员表单
     */
    public function edit(Project $project, ProjectMember $member)
    {
        // 检查成员是否属于该项目
        if ($member->project_id !== $project->id) {
            abort(404);
        }

        // 检查用户是否有权限管理项目成员
        $this->authorize('manageMember', $project);

        // 不能编辑项目所有者
        if ($member->isOwner()) {
            abort(403, '不能编辑项目所有者');
        }

        return view('user-admin.members.edit', compact('project', 'member'));
    }

    /**
     * 更新成员角色
     */
    public function update(Request $request, Project $project, ProjectMember $member): JsonResponse
    {
        // 检查成员是否属于该项目
        if ($member->project_id !== $project->id) {
            return response()->json([
                'success' => false,
                'message' => '成员不属于该项目',
            ], 404);
        }

        // 检查用户是否有权限管理项目成员
        $this->authorize('manageMember', $project);

        $validator = Validator::make($request->all(), [
            'role' => 'required|in:admin,member,viewer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => '验证失败',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $this->memberService->updateMemberRole($member, $request->role);

            return response()->json([
                'success' => true,
                'message' => '成员角色更新成功',
                'data' => [
                    'member' => $member->fresh()->load('user'),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '更新成员角色失败: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 移除项目成员
     */
    public function destroy(Project $project, ProjectMember $member): JsonResponse
    {
        // 检查成员是否属于该项目
        if ($member->project_id !== $project->id) {
            return response()->json([
                'success' => false,
                'message' => '成员不属于该项目',
            ], 404);
        }

        // 检查用户是否有权限管理项目成员
        $this->authorize('manageMember', $project);

        try {
            $user = $member->user;
            $this->memberService->removeMember($project, $user);

            return response()->json([
                'success' => true,
                'message' => '成员移除成功',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '移除成员失败: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 批量添加成员
     */
    public function batchAdd(Request $request, Project $project): JsonResponse
    {
        // 检查用户是否有权限管理项目成员
        $this->authorize('manageMember', $project);

        $validator = Validator::make($request->all(), [
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
            'role' => 'required|in:admin,member,viewer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => '验证失败',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $users = User::whereIn('id', $request->user_ids)->get();
            $members = $this->memberService->addMembers($project, $users->toArray(), $request->role);

            return response()->json([
                'success' => true,
                'message' => "成功添加 {$members->count()} 个成员",
                'data' => [
                    'members' => $members->load('user'),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '批量添加成员失败: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 转移项目所有权
     */
    public function transferOwnership(Request $request, Project $project): JsonResponse
    {
        // 只有项目所有者可以转移所有权
        if (!$project->isOwner(Auth::user())) {
            return response()->json([
                'success' => false,
                'message' => '只有项目所有者可以转移所有权',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'new_owner_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => '验证失败',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $newOwner = User::findOrFail($request->new_owner_id);
            $this->memberService->transferOwnership($project, $newOwner);

            return response()->json([
                'success' => true,
                'message' => '项目所有权转移成功',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '转移所有权失败: ' . $e->getMessage(),
            ], 500);
        }
    }
}
