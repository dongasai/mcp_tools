<?php

namespace App\Admin\Actions;

use App\Modules\User\Models\User;
use Dcat\Admin\Actions\Response;
use Dcat\Admin\Grid\RowAction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ResetPasswordAction extends RowAction
{
    /**
     * 标题
     *
     * @return string
     */
    public function title()
    {
        return '重置密码';
    }

    /**
     * 确认信息
     *
     * @return string|array|void
     */
    public function confirm()
    {
        return [
            '确认重置密码？',
            '重置后将生成随机密码，请及时通知用户修改密码。',
        ];
    }

    /**
     * 处理请求
     *
     * @param Request $request
     *
     * @return Response
     */
    public function handle(Request $request)
    {
        // 获取用户ID
        $userId = $this->getKey();
        
        // 查找用户
        $user = User::find($userId);
        
        if (!$user) {
            return $this->response()
                ->error('用户不存在');
        }

        // 生成随机密码（8位字符，包含字母和数字）
        $newPassword = Str::random(8);
        
        // 更新用户密码
        $user->password = Hash::make($newPassword);
        $user->save();

        // 返回成功响应，显示新密码（永久显示，点击消失）
        return $this->response()
            ->success("密码重置成功！新密码：{$newPassword}")
            ->timeout(0) // 设置为0表示不自动消失
            ->refresh(); // 刷新页面
    }

    /**
     * 设置按钮样式
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        // 设置图标
        $this->setHtmlAttribute('title', '重置用户密码');
    }

    /**
     * 按钮HTML
     *
     * @return string
     */
    public function html()
    {
        return <<<HTML
<a {$this->formatHtmlAttributes()} class="btn btn-sm btn-warning">
    <i class="feather icon-refresh-cw"></i> {$this->title()}
</a>
HTML;
    }
}
