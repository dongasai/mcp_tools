<?php

namespace App\UserAdmin\Controllers;

use Dcat\Admin\Http\Controllers\AdminController;
use Dcat\Admin\Grid;
use Dcat\Admin\Form;
use Dcat\Admin\Show;
use Dcat\Admin\Layout\Content;
use App\Modules\Project\Models\Project;
use App\Modules\Project\Models\ProjectMember;
use Modules\User\Models\User;
use App\Services\MemberService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MemberController extends AdminController
{
    protected $title = '项目成员管理';
    protected MemberService $memberService;

    public function __construct(MemberService $memberService)
    {
        $this->memberService = $memberService;
    }

    /**
     * 显示项目成员列表
     */
    public function index(Content $content)
    {
        // 从路由参数获取项目ID
        $projectId = request()->route('project');
        $project = Project::findOrFail($projectId);

        // 获取当前用户
        $currentUser = $this->getCurrentUser();

        // 检查权限
        if (!$project->hasMember($currentUser) && $project->user_id !== $currentUser->id) {
            abort(403, '您没有权限查看此项目的成员');
        }

        return $content
            ->title($this->title)
            ->description("项目：{$project->name}")
            ->body($this->grid($project));
    }

    /**
     * 成员列表网格
     */
    protected function grid(Project $project)
    {
        $grid = new Grid(new ProjectMember());

        // 只显示当前项目的成员
        $grid->model()->where('project_id', $project->id)->with('user');

        $grid->column('id', 'ID')->sortable();
        $grid->column('user.name', '用户姓名');
        $grid->column('user.email', '邮箱');
        $grid->column('role', '角色')->using([
            'owner' => '项目所有者',
            'admin' => '管理员',
            'member' => '成员',
            'viewer' => '查看者'
        ])->label([
            'owner' => 'danger',
            'admin' => 'warning',
            'member' => 'success',
            'viewer' => 'info'
        ]);
        $grid->column('joined_at', '加入时间')->display(function ($value) {
            return $value ? date('Y-m-d H:i:s', strtotime($value)) : '-';
        });
        $grid->column('created_at', '创建时间')->display(function ($value) {
            return date('Y-m-d H:i:s', strtotime($value));
        });

        // 操作按钮
        $grid->actions(function (Grid\Displayers\Actions $actions) use ($project) {
            $member = $actions->row;
            $currentUser = $this->getCurrentUser();

            // 不能操作项目所有者
            if ($member->role === 'owner') {
                $actions->disableDelete();
                $actions->disableEdit();
            }

            // 只有项目所有者和管理员可以管理成员
            if (!$project->isAdmin($currentUser) && $project->user_id !== $currentUser->id) {
                $actions->disableAll();
            }
        });

        // 工具栏
        $grid->tools(function (Grid\Tools $tools) use ($project) {
            $currentUser = $this->getCurrentUser();
            // 只有项目所有者和管理员可以添加成员
            if ($project->isAdmin($currentUser) || $project->user_id === $currentUser->id) {
                $tools->append('<a href="'.admin_url("projects/{$project->id}/members/create").'" class="btn btn-sm btn-success"><i class="fa fa-plus"></i> 添加成员</a>');
            }
        });

        $grid->disableCreateButton();
        $grid->disableExport();
        $grid->disableBatchActions();

        return $grid;
    }

    /**
     * 显示添加成员表单
     */
    public function create(Content $content)
    {
        $projectId = request()->route('project');
        $project = Project::findOrFail($projectId);

        // 检查权限
        $currentUser = $this->getCurrentUser();
        if (!$project->isAdmin($currentUser) && $project->user_id !== $currentUser->id) {
            abort(403, '您没有权限管理此项目的成员');
        }

        return $content
            ->title('添加项目成员')
            ->description("项目：{$project->name}")
            ->body($this->form($project));
    }

    /**
     * 成员表单
     */
    protected function form(Project $project)
    {
        $form = new Form(new ProjectMember());

        // 获取不是项目成员的用户列表
        $availableUsers = User::whereNotIn('id', function ($query) use ($project) {
            $query->select('user_id')
                  ->from('project_members')
                  ->where('project_id', $project->id);
        })->pluck('name', 'id');

        $form->hidden('project_id')->value($project->id);
        $form->select('user_id', '选择用户')
             ->options($availableUsers)
             ->required()
             ->help('选择要添加到项目的用户');

        $form->select('role', '角色')
             ->options([
                 'admin' => '管理员',
                 'member' => '成员',
                 'viewer' => '查看者'
             ])
             ->default('member')
             ->required()
             ->help('设置用户在项目中的角色');

        // 保存前处理
        $form->saving(function (Form $form) use ($project) {
            $user = User::find($form->user_id);
            if (!$user) {
                throw new \Exception('用户不存在');
            }

            if ($project->hasMember($user)) {
                throw new \Exception('该用户已经是项目成员');
            }
        });

        // 保存后处理
        $form->saved(function (Form $form, $result) use ($project) {
            $user = User::find($form->user_id);
            $this->memberService->addMember($project, $user, $form->role);

            admin_toastr('成员添加成功', 'success');
            return redirect(admin_url("projects/{$project->id}/members"));
        });

        return $form;
    }

    /**
     * 显示成员详情
     */
    public function show($id, Content $content)
    {
        $projectId = request()->route('project');
        $member = ProjectMember::findOrFail($id);
        $project = Project::findOrFail($projectId);

        // 检查成员是否属于该项目
        if ($member->project_id !== $project->id) {
            abort(404);
        }

        // 检查权限
        $currentUser = $this->getCurrentUser();
        if (!$project->hasMember($currentUser) && $project->user_id !== $currentUser->id) {
            abort(403, '您没有权限查看此项目的成员');
        }

        return $content
            ->title('成员详情')
            ->description("项目：{$project->name}")
            ->body($this->detail($member));
    }

    /**
     * 成员详情
     */
    protected function detail(ProjectMember $member)
    {
        $show = new Show($member);

        $show->field('id', 'ID');
        $show->field('user.name', '用户姓名');
        $show->field('user.email', '邮箱');
        $show->field('role', '角色')->using([
            'owner' => '项目所有者',
            'admin' => '管理员',
            'member' => '成员',
            'viewer' => '查看者'
        ]);
        $show->field('permissions', '权限')->json();
        $show->field('joined_at', '加入时间');
        $show->field('created_at', '创建时间');
        $show->field('updated_at', '更新时间');

        return $show;
    }

    /**
     * 编辑成员
     */
    public function edit($id, Content $content)
    {
        $projectId = request()->route('project');
        $member = ProjectMember::findOrFail($id);
        $project = Project::findOrFail($projectId);

        // 检查成员是否属于该项目
        if ($member->project_id !== $project->id) {
            abort(404);
        }

        // 检查权限
        $currentUser = $this->getCurrentUser();
        if (!$project->isAdmin($currentUser) && $project->user_id !== $currentUser->id) {
            abort(403, '您没有权限管理此项目的成员');
        }

        // 不能编辑项目所有者
        if ($member->role === 'owner') {
            abort(403, '不能编辑项目所有者');
        }

        return $content
            ->title('编辑成员')
            ->description("项目：{$project->name}")
            ->body($this->editForm($member));
    }

    /**
     * 编辑表单
     */
    protected function editForm(ProjectMember $member)
    {
        $form = new Form($member);

        $form->display('user.name', '用户姓名');
        $form->display('user.email', '邮箱');

        $form->select('role', '角色')
             ->options([
                 'admin' => '管理员',
                 'member' => '成员',
                 'viewer' => '查看者'
             ])
             ->required()
             ->help('修改用户在项目中的角色');

        // 保存后处理
        $form->saved(function (Form $form, $result) {
            $this->memberService->updateMemberRole($result, $form->role);
            admin_toastr('成员角色更新成功', 'success');
        });

        return $form;
    }

    /**
     * 删除成员
     */
    public function destroy($id)
    {
        $projectId = request()->route('project');
        $member = ProjectMember::findOrFail($id);
        $project = Project::findOrFail($projectId);

        // 检查成员是否属于该项目
        if ($member->project_id !== $project->id) {
            return response()->json(['status' => false, 'message' => '成员不属于该项目']);
        }

        // 检查权限
        $currentUser = $this->getCurrentUser();
        if (!$project->isAdmin($currentUser) && $project->user_id !== $currentUser->id) {
            return response()->json(['status' => false, 'message' => '您没有权限管理此项目的成员']);
        }

        try {
            $user = $member->user;
            $this->memberService->removeMember($project, $user);

            return response()->json(['status' => true, 'message' => '成员移除成功']);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => '移除成员失败: ' . $e->getMessage()]);
        }
    }

    /**
     * 获取当前用户
     */
    protected function getCurrentUser()
    {
        $userAdminUser = auth('user-admin')->user();
        return User::where('name', $userAdminUser->name)->first();
    }
}
