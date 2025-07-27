<?php

namespace App\Policies;

use Modules\Project\Models\Project;
use Modules\Project\Models\ProjectMember;
use Modules\User\Models\User;
use Illuminate\Auth\Access\Response;

class ProjectPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true; // 用户可以查看自己的项目列表
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Project $project): bool
    {
        // 项目所有者或项目成员可以查看
        return $project->user_id === $user->id || $project->hasMember($user);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true; // 所有认证用户都可以创建项目
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Project $project): bool
    {
        // 项目所有者或管理员可以更新
        return $project->user_id === $user->id || $project->isAdmin($user);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Project $project): bool
    {
        // 只有项目所有者可以删除项目
        return $project->user_id === $user->id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Project $project): bool
    {
        return $this->delete($user, $project);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Project $project): bool
    {
        return $this->delete($user, $project);
    }

    /**
     * Determine whether the user can manage project members.
     */
    public function manageMember(User $user, Project $project): bool
    {
        // 项目所有者或管理员可以管理成员
        return $project->user_id === $user->id || $project->isAdmin($user);
    }

    /**
     * Determine whether the user can manage project settings.
     */
    public function manageSettings(User $user, Project $project): bool
    {
        // 项目所有者或管理员可以管理设置
        return $project->user_id === $user->id || $project->isAdmin($user);
    }

    /**
     * Determine whether the user can transfer ownership.
     */
    public function transferOwnership(User $user, Project $project): bool
    {
        // 只有项目所有者可以转移所有权
        return $project->user_id === $user->id;
    }
}
