<?php

namespace App\UserAdmin\Controllers;

use Dcat\Admin\Http\Controllers\AdminController;
use Dcat\Admin\Form;
use Dcat\Admin\Layout\Content;
use Dcat\Admin\Widgets\Card;
use App\Modules\User\Models\User;
use Illuminate\Http\Request;

class ProfileController extends AdminController
{
    protected $title = '个人设置';

    public function index(Content $content)
    {
        return $content
            ->title($this->title)
            ->description('管理您的个人信息和偏好设置')
            ->body($this->profileForm());
    }

    protected function profileForm()
    {
        $user = $this->getCurrentUser();

        $form = new Form($user ?: new User());

        $form->tab('基本信息', function ($form) {
            $form->text('name', '姓名')->required();
            $form->email('email', '邮箱')->required();
            $form->text('phone', '手机号码');
            $form->textarea('bio', '个人简介')->rows(3);
            $form->image('avatar', '头像')->uniqueName();
        });

        $form->tab('偏好设置', function ($form) {
            $form->select('timezone', '时区')->options([
                'Asia/Shanghai' => '北京时间 (UTC+8)',
                'UTC' => '协调世界时 (UTC)',
                'America/New_York' => '纽约时间 (UTC-5)',
                'Europe/London' => '伦敦时间 (UTC+0)',
            ])->default('Asia/Shanghai');

            $form->select('language', '语言')->options([
                'zh-CN' => '简体中文',
                'en' => 'English',
            ])->default('zh-CN');

            $form->select('theme', '主题')->options([
                'light' => '浅色主题',
                'dark' => '深色主题',
                'auto' => '跟随系统',
            ])->default('light');
        });

        $form->tab('通知设置', function ($form) {
            $form->checkbox('notification_preferences', '通知偏好')->options([
                'email_task_assigned' => '任务分配邮件通知',
                'email_task_completed' => '任务完成邮件通知',
                'email_project_updates' => '项目更新邮件通知',
                'email_agent_alerts' => 'Agent异常邮件通知',
                'browser_notifications' => '浏览器推送通知',
                'mobile_notifications' => '移动端推送通知',
            ])->default(['email_task_assigned', 'email_project_updates']);
        });

        $form->tab('安全设置', function ($form) {
            $form->password('password', '新密码')->help('留空则不修改密码');
            $form->password('password_confirmation', '确认密码');

            $form->switch('two_factor_enabled', '双因素认证')->default(false);
            $form->textarea('api_tokens', 'API令牌')->readonly()->help('用于第三方集成的API令牌');
        });

        // 处理表单提交
        $form->saving(function (Form $form) {
            // 如果没有填写密码，则不更新密码字段
            if (empty($form->password)) {
                $form->deleteInput('password');
                $form->deleteInput('password_confirmation');
            } else {
                // 验证密码确认
                if ($form->password !== $form->password_confirmation) {
                    return back()->withErrors(['password_confirmation' => '密码确认不匹配']);
                }
                $form->password = bcrypt($form->password);
                $form->deleteInput('password_confirmation');
            }
        });

        $form->tools(function (Form\Tools $tools) {
            $tools->disableDelete();
            $tools->disableView();
        });

        return $form;
    }

    public function update(Request $request)
    {
        $user = $this->getCurrentUser();

        if (!$user) {
            return response()->json(['status' => false, 'message' => '用户不存在']);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'bio' => 'nullable|string|max:500',
            'timezone' => 'required|string',
            'language' => 'required|string',
            'theme' => 'required|string',
            'notification_preferences' => 'nullable|array',
        ]);

        $user->update($validated);

        return response()->json(['status' => true, 'message' => '设置已保存']);
    }

    protected function getCurrentUser()
    {
        $userAdminUser = auth('user-admin')->user();
        return User::where('name', $userAdminUser->name)->first();
    }

    /**
     * 显示用户统计信息
     */
    protected function userStatsCard($user)
    {
        $stats = [
            'projects_count' => $user->projects()->count() ?? 0,
            'tasks_created' => $user->createdTasks()->count() ?? 0,
            'tasks_completed' => $user->assignedTasks()->where('status', 'completed')->count() ?? 0,
            'agents_count' => $user->agents()->count() ?? 0,
            'join_date' => $user->created_at->format('Y-m-d'),
            'last_login' => $user->last_login_at ? $user->last_login_at->diffForHumans() : '从未登录',
        ];

        return Card::make('个人统计', view('user-admin::widgets.user-stats', compact('stats')));
    }
}
