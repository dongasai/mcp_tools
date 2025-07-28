<?php

namespace Modules\UserAdmin\Http\Controllers\UserAdmin;

use Dcat\Admin\Http\Controllers\AdminController;
use Dcat\Admin\Grid;
use Dcat\Admin\Form;
use Dcat\Admin\Show;
use Dcat\Admin\Layout\Content;
use Modules\Project\Models\Project;
use Modules\User\Models\User;

class ProjectController extends AdminController
{
    protected $title = '项目管理';

    public function index(Content $content)
    {
        return $content
            ->title($this->title)
            ->description('管理您的项目')
            ->body($this->grid());
    }

    protected function grid()
    {
        $grid = new Grid(new Project());

        // 只显示当前用户的项目
        $user = $this->getCurrentUser();
        if ($user) {
            $grid->model()->where('user_id', $user->id);
        } else {
            // 如果无法获取用户，不显示任何项目
            $grid->model()->where('id', -1);
        }

        $grid->column('id', 'ID')->sortable();
        $grid->column('name', '项目名称')->limit(30);
        $grid->column('description', '描述')->limit(50);
        $grid->column('status', '状态')->using([
            'active' => '进行中',
            'inactive' => '未激活',
            'archived' => '已归档'
        ])->label([
            'active' => 'success',
            'inactive' => 'warning',
            'archived' => 'default'
        ]);

        $grid->column('created_at', '创建时间')->sortable();

        // 简化统计信息，避免关联查询
        $grid->column('tasks_count', '任务数量')->display(function () {
            return '0'; // 暂时显示0，后续完善
        });

        $grid->column('members_count', '成员数量')->display(function () {
            return $this->members()->count();
        });

        $grid->filter(function($filter) {
            $filter->like('name', '项目名称');
            $filter->equal('status', '状态')->select([
                'active' => '进行中',
                'inactive' => '未激活',
                'archived' => '已归档'
            ]);
        });

        $grid->actions(function ($actions) {
            $actions->disableDelete(); // 禁用删除，改为归档

            // 添加成员管理按钮
            $project = $actions->row;
            $actions->append('<a href="'.admin_url("projects/{$project->id}/members").'" class="btn btn-xs btn-primary" title="成员管理"><i class="fa fa-users"></i></a>');
        });

        return $grid;
    }

    protected function form()
    {
        $form = new Form(new Project());

        // 自动设置当前用户ID
        $user = $this->getCurrentUser();
        $form->hidden('user_id')->default($user ? $user->id : 1);

        $form->text('name', '项目名称')->required();
        $form->textarea('description', '项目描述');

        $form->select('status', '状态')->options([
            'active' => '进行中',
            'inactive' => '未激活',
            'archived' => '已归档'
        ])->default('active');

        $form->text('repository_url', 'Git仓库地址');
        $form->textarea('settings', '项目设置')->help('JSON格式的项目配置')->default('{}');

        // 保存时设置当前用户ID
        $form->saving(function (Form $form) {
            $user = auth('user-admin')->user();
            if ($user) {
                $form->model()->user_id = $user->id;
            } else {
                throw new \Exception('无法获取当前用户信息');
            }
        });

        // 暂时移除复杂的关联逻辑
        // $form->saved(function (Form $form, $result) {
        //     // 后续完善项目成员关联
        // });

        return $form;
    }

    protected function detail($id)
    {
        $show = new Show(Project::findOrFail($id));

        $show->field('id', 'ID');
        $show->field('name', '项目名称');
        $show->field('description', '项目描述');
        $show->field('status', '状态')->using([
            'active' => '进行中',
            'inactive' => '未激活',
            'archived' => '已归档'
        ]);
        $show->field('repository_url', 'Git仓库地址');
        $show->field('created_at', '创建时间');
        $show->field('updated_at', '更新时间');

        // 项目统计信息
        $show->field('stats', '项目统计')->as(function () {
            $project = $this;
            return [
                '任务总数' => $project->tasks()->count(),
                '已完成任务' => $project->tasks()->where('status', 'completed')->count(),
                '成员数量' => $project->members()->count(),
                '活跃Agent' => 0 // 后续完善
            ];
        })->as(function ($stats) {
            $html = '<ul>';
            foreach ($stats as $key => $value) {
                $html .= "<li><strong>{$key}:</strong> {$value}</li>";
            }
            $html .= '</ul>';
            return $html;
        });

        // 项目成员信息
        $show->field('members', '项目成员')->as(function () {
            $project = $this;
            $members = $project->membersWithUsers;

            if ($members->isEmpty()) {
                return '<p>暂无成员</p>';
            }

            $html = '<div class="table-responsive"><table class="table table-sm">';
            $html .= '<thead><tr><th>姓名</th><th>邮箱</th><th>角色</th><th>加入时间</th></tr></thead><tbody>';

            foreach ($members as $member) {
                $roleLabels = [
                    'owner' => '<span class="label label-danger">项目所有者</span>',
                    'admin' => '<span class="label label-warning">管理员</span>',
                    'member' => '<span class="label label-success">成员</span>',
                    'viewer' => '<span class="label label-info">查看者</span>'
                ];

                $html .= '<tr>';
                $html .= '<td>' . $member->user->name . '</td>';
                $html .= '<td>' . $member->user->email . '</td>';
                $html .= '<td>' . ($roleLabels[$member->role] ?? $member->role) . '</td>';
                $html .= '<td>' . $member->joined_at->format('Y-m-d H:i') . '</td>';
                $html .= '</tr>';
            }

            $html .= '</tbody></table></div>';
            $html .= '<div class="mt-2">';
            $html .= '<a href="'.admin_url("projects/{$project->id}/members").'" class="btn btn-sm btn-primary"><i class="fa fa-users"></i> 管理成员</a>';
            $html .= '</div>';

            return $html;
        });

        return $show;
    }

    protected function getCurrentUser()
    {
        $userAdminUser = auth('user-admin')->user();
        if (!$userAdminUser) {
            return null;
        }

        // 直接返回认证的用户，因为user-admin guard使用的就是User模型
        return $userAdminUser;
    }
}
