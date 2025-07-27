<?php

namespace App\Services;

use App\Modules\Project\Models\Project;
use App\Modules\Project\Models\ProjectMember;
use Modules\User\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MemberService
{
    /**
     * 添加项目成员
     */
    public function addMember(Project $project, User $user, string $role = ProjectMember::ROLE_MEMBER): ProjectMember
    {
        // 检查用户是否已经是项目成员
        if ($project->hasMember($user)) {
            throw new \InvalidArgumentException('用户已经是项目成员');
        }

        // 验证角色
        $this->validateRole($role);

        DB::beginTransaction();
        try {
            $member = ProjectMember::create([
                'project_id' => $project->id,
                'user_id' => $user->id,
                'role' => $role,
                'permissions' => ProjectMember::getDefaultPermissions($role),
                'joined_at' => now(),
            ]);

            // 记录日志
            Log::info('项目成员添加成功', [
                'project_id' => $project->id,
                'user_id' => $user->id,
                'role' => $role,
                'member_id' => $member->id,
            ]);

            DB::commit();
            return $member;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('添加项目成员失败', [
                'project_id' => $project->id,
                'user_id' => $user->id,
                'role' => $role,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * 移除项目成员
     */
    public function removeMember(Project $project, User $user): bool
    {
        $member = $project->members()->where('user_id', $user->id)->first();
        
        if (!$member) {
            throw new \InvalidArgumentException('用户不是项目成员');
        }

        // 不能移除项目所有者
        if ($member->isOwner()) {
            throw new \InvalidArgumentException('不能移除项目所有者');
        }

        DB::beginTransaction();
        try {
            $member->delete();

            // 记录日志
            Log::info('项目成员移除成功', [
                'project_id' => $project->id,
                'user_id' => $user->id,
                'role' => $member->role,
            ]);

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('移除项目成员失败', [
                'project_id' => $project->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * 更新成员角色
     */
    public function updateMemberRole(ProjectMember $member, string $role): bool
    {
        // 验证角色
        $this->validateRole($role);

        // 不能修改项目所有者的角色
        if ($member->isOwner()) {
            throw new \InvalidArgumentException('不能修改项目所有者的角色');
        }

        DB::beginTransaction();
        try {
            $oldRole = $member->role;
            
            $member->update([
                'role' => $role,
                'permissions' => ProjectMember::getDefaultPermissions($role),
            ]);

            // 记录日志
            Log::info('项目成员角色更新成功', [
                'member_id' => $member->id,
                'project_id' => $member->project_id,
                'user_id' => $member->user_id,
                'old_role' => $oldRole,
                'new_role' => $role,
            ]);

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('更新项目成员角色失败', [
                'member_id' => $member->id,
                'role' => $role,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * 获取项目成员列表
     */
    public function getMembers(Project $project): Collection
    {
        return $project->membersWithUsers()->orderBy('role')->orderBy('joined_at')->get();
    }

    /**
     * 检查用户是否为项目成员
     */
    public function isMember(Project $project, User $user): bool
    {
        return $project->hasMember($user);
    }

    /**
     * 获取用户在项目中的角色
     */
    public function getUserRole(Project $project, User $user): ?string
    {
        return $project->getUserRole($user);
    }

    /**
     * 批量添加成员
     */
    public function addMembers(Project $project, array $users, string $role = ProjectMember::ROLE_MEMBER): Collection
    {
        $this->validateRole($role);
        
        $members = collect();
        
        DB::beginTransaction();
        try {
            foreach ($users as $user) {
                if (!$project->hasMember($user)) {
                    $member = ProjectMember::create([
                        'project_id' => $project->id,
                        'user_id' => $user->id,
                        'role' => $role,
                        'permissions' => ProjectMember::getDefaultPermissions($role),
                        'joined_at' => now(),
                    ]);
                    $members->push($member);
                }
            }

            // 记录日志
            Log::info('批量添加项目成员成功', [
                'project_id' => $project->id,
                'added_count' => $members->count(),
                'role' => $role,
            ]);

            DB::commit();
            return $members;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('批量添加项目成员失败', [
                'project_id' => $project->id,
                'role' => $role,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * 获取用户参与的项目列表
     */
    public function getUserProjects(User $user): Collection
    {
        return Project::whereHas('members', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->with(['members' => function ($query) use ($user) {
            $query->where('user_id', $user->id);
        }])->get();
    }

    /**
     * 转移项目所有权
     */
    public function transferOwnership(Project $project, User $newOwner): bool
    {
        if (!$project->hasMember($newOwner)) {
            throw new \InvalidArgumentException('新所有者必须是项目成员');
        }

        DB::beginTransaction();
        try {
            // 将当前所有者改为管理员
            $currentOwner = $project->members()->where('role', ProjectMember::ROLE_OWNER)->first();
            if ($currentOwner) {
                $currentOwner->update(['role' => ProjectMember::ROLE_ADMIN]);
            }

            // 将新用户设为所有者
            $newOwnerMember = $project->members()->where('user_id', $newOwner->id)->first();
            $newOwnerMember->update([
                'role' => ProjectMember::ROLE_OWNER,
                'permissions' => ProjectMember::getDefaultPermissions(ProjectMember::ROLE_OWNER),
            ]);

            // 记录日志
            Log::info('项目所有权转移成功', [
                'project_id' => $project->id,
                'old_owner_id' => $currentOwner?->user_id,
                'new_owner_id' => $newOwner->id,
            ]);

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('项目所有权转移失败', [
                'project_id' => $project->id,
                'new_owner_id' => $newOwner->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * 验证角色
     */
    private function validateRole(string $role): void
    {
        $validRoles = [
            ProjectMember::ROLE_OWNER,
            ProjectMember::ROLE_ADMIN,
            ProjectMember::ROLE_MEMBER,
            ProjectMember::ROLE_VIEWER,
        ];

        if (!in_array($role, $validRoles)) {
            throw new \InvalidArgumentException("无效的角色: {$role}");
        }
    }
}
