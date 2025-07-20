<?php

namespace App\UserAdmin\Controllers;

use App\Modules\User\Models\User;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;

class UserController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(User::query(), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('name', '姓名');
            $grid->column('email', '邮箱');
            $grid->column('role', '角色')->using([
                User::ROLE_SUPER_ADMIN => '超级管理员',
                User::ROLE_ADMIN => '管理员',
                User::ROLE_USER => '普通用户',
            ])->label([
                User::ROLE_SUPER_ADMIN => 'danger',
                User::ROLE_ADMIN => 'warning',
                User::ROLE_USER => 'success',
            ]);
            $grid->column('status', '状态')->using([
                User::STATUS_ACTIVE => '激活',
                User::STATUS_INACTIVE => '未激活',
                User::STATUS_SUSPENDED => '暂停',
                User::STATUS_PENDING => '待审核',
            ])->label([
                User::STATUS_ACTIVE => 'success',
                User::STATUS_INACTIVE => 'default',
                User::STATUS_SUSPENDED => 'danger',
                User::STATUS_PENDING => 'warning',
            ]);
            $grid->column('email_verified_at', '邮箱验证时间');
            $grid->column('last_login_at', '最后登录时间');
            $grid->column('created_at', '创建时间');
            $grid->column('updated_at', '更新时间');

            $grid->filter(function (Grid\Filter $filter) {
                $filter->like('name', '姓名');
                $filter->like('email', '邮箱');
                $filter->equal('role', '角色')->select([
                    User::ROLE_SUPER_ADMIN => '超级管理员',
                    User::ROLE_ADMIN => '管理员',
                    User::ROLE_USER => '普通用户',
                ]);
                $filter->equal('status', '状态')->select([
                    User::STATUS_ACTIVE => '激活',
                    User::STATUS_INACTIVE => '未激活',
                    User::STATUS_SUSPENDED => '暂停',
                    User::STATUS_PENDING => '待审核',
                ]);
            });

            $grid->actions(function (Grid\Displayers\Actions $actions) {
                // 禁止删除当前登录用户
                if ($actions->getKey() == auth('user-admin')->id()) {
                    $actions->disableDelete();
                }
            });
        });
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        return Show::make($id, User::query(), function (Show $show) {
            $show->field('id');
            $show->field('name', '姓名');
            $show->field('email', '邮箱');
            $show->field('role', '角色')->using([
                User::ROLE_SUPER_ADMIN => '超级管理员',
                User::ROLE_ADMIN => '管理员',
                User::ROLE_USER => '普通用户',
            ]);
            $show->field('status', '状态')->using([
                User::STATUS_ACTIVE => '激活',
                User::STATUS_INACTIVE => '未激活',
                User::STATUS_SUSPENDED => '暂停',
                User::STATUS_PENDING => '待审核',
            ]);
            $show->field('avatar', '头像')->image();
            $show->field('timezone', '时区');
            $show->field('locale', '语言');
            $show->field('email_verified_at', '邮箱验证时间');
            $show->field('last_login_at', '最后登录时间');
            $show->field('last_login_ip', '最后登录IP');
            $show->field('created_at', '创建时间');
            $show->field('updated_at', '更新时间');

            // 显示用户统计信息
            $show->divider();
            $show->field('projects_count', '项目数量')->as(function () {
                return $this->projects()->count();
            });
            $show->field('created_tasks_count', '创建的任务数量')->as(function () {
                return $this->createdTasks()->count();
            });
            $show->field('assigned_tasks_count', '分配的任务数量')->as(function () {
                return $this->assignedTasks()->count();
            });
            $show->field('agents_count', 'Agent数量')->as(function () {
                return $this->agents()->count();
            });
        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Form::make(User::query(), function (Form $form) {
            $form->display('id');
            $form->text('name', '姓名')->required();
            $form->email('email', '邮箱')->required();
            
            // 只有在创建时才显示密码字段
            if ($form->isCreating()) {
                $form->password('password', '密码')->required();
            } else {
                $form->password('password', '密码（留空则不修改）');
            }

            $form->select('role', '角色')->options([
                User::ROLE_SUPER_ADMIN => '超级管理员',
                User::ROLE_ADMIN => '管理员',
                User::ROLE_USER => '普通用户',
            ])->default(User::ROLE_USER)->required();

            $form->select('status', '状态')->options([
                User::STATUS_ACTIVE => '激活',
                User::STATUS_INACTIVE => '未激活',
                User::STATUS_SUSPENDED => '暂停',
                User::STATUS_PENDING => '待审核',
            ])->default(User::STATUS_ACTIVE)->required();

            $form->image('avatar', '头像')->disk('public');
            $form->text('timezone', '时区')->default(config('app.timezone'));
            $form->text('locale', '语言')->default('zh_CN');

            // 保存时处理密码
            $form->saving(function (Form $form) {
                if ($form->password && $form->model()->password != $form->password) {
                    $form->password = bcrypt($form->password);
                } elseif (!$form->password) {
                    $form->deleteInput('password');
                }
            });

            // 禁止删除当前登录用户
            $form->tools(function (Form\Tools $tools) use ($form) {
                if ($form->model()->id == auth('user-admin')->id()) {
                    $tools->disableDelete();
                }
            });
        });
    }
}
