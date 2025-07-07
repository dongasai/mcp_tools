<?php

namespace App\UserAdmin\Controllers;

use Dcat\Admin\Http\Controllers\AdminController;
use Dcat\Admin\Grid;
use Dcat\Admin\Form;
use Dcat\Admin\Show;
use Dcat\Admin\Layout\Content;
use App\Modules\Project\Models\Project;
use App\Modules\User\Models\User;

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
            $grid->model()->whereHas('members', function($query) use ($user) {
                $query->where('user_id', $user->id);
            });
        }

        $grid->column('id', 'ID')->sortable();
        $grid->column('name', '项目名称')->limit(30);
        $grid->column('description', '描述')->limit(50);
        $grid->column('status', '状态')->using([
            'active' => '进行中',
            'completed' => '已完成',
            'paused' => '已暂停',
            'cancelled' => '已取消'
        ])->label([
            'active' => 'success',
            'completed' => 'primary',
            'paused' => 'warning',
            'cancelled' => 'danger'
        ]);

        $grid->column('created_at', '创建时间')->sortable();

        // 统计信息
        $grid->column('tasks_count', '任务数量')->display(function () {
            return $this->tasks()->count();
        });

        $grid->column('members_count', '成员数量')->display(function () {
            return $this->members()->count();
        });

        $grid->filter(function($filter) {
            $filter->like('name', '项目名称');
            $filter->equal('status', '状态')->select([
                'active' => '进行中',
                'completed' => '已完成',
                'paused' => '已暂停',
                'cancelled' => '已取消'
            ]);
        });

        $grid->actions(function ($actions) {
            $actions->disableDelete(); // 禁用删除，改为归档
            $actions->add(new \App\UserAdmin\Actions\ArchiveProjectAction());
        });

        return $grid;
    }

    protected function form()
    {
        $form = new Form(new Project());

        $form->text('name', '项目名称')->required();
        $form->textarea('description', '项目描述');

        $form->select('status', '状态')->options([
            'active' => '进行中',
            'completed' => '已完成',
            'paused' => '已暂停',
            'cancelled' => '已取消'
        ])->default('active');

        $form->text('repository_url', 'Git仓库地址');
        $form->json('settings', '项目设置')->default('{}');

        // 保存时自动关联当前用户
        $form->saving(function (Form $form) {
            $user = $this->getCurrentUser();
            if ($user && !$form->model()->id) {
                // 新建项目时设置创建者
                $form->model()->user_id = $user->id;
            }
        });

        $form->saved(function (Form $form, $result) {
            $user = $this->getCurrentUser();
            if ($user && $result) {
                // 确保创建者成为项目成员
                $form->model()->members()->syncWithoutDetaching([
                    $user->id => ['role' => 'owner', 'joined_at' => now()]
                ]);
            }
        });

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
            'completed' => '已完成',
            'paused' => '已暂停',
            'cancelled' => '已取消'
        ]);
        $show->field('repository_url', 'Git仓库地址');
        $show->field('created_at', '创建时间');
        $show->field('updated_at', '更新时间');

        // 显示项目统计
        $show->field('stats', '项目统计')->as(function () {
            return [
                '任务总数' => $this->tasks()->count(),
                '已完成任务' => $this->tasks()->where('status', 'completed')->count(),
                '成员数量' => $this->members()->count(),
                '活跃Agent' => $this->agents()->where('status', 'active')->count()
            ];
        })->json();

        return $show;
    }

    protected function getCurrentUser()
    {
        $userAdminUser = auth('user-admin')->user();
        return User::where('name', $userAdminUser->name)->first();
    }
}
