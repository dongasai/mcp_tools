<?php

namespace App\UserAdmin\Actions\Grid;

use App\Modules\Agent\Models\AgentQuestion;
use Dcat\Admin\Actions\Response;
use Dcat\Admin\Grid\RowAction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IgnoreQuestionAction extends RowAction
{
    /**
     * 标题
     *
     * @return string
     */
    public function title()
    {
        return '忽略';
    }

    /**
     * 确认信息
     *
     * @return string|array|void
     */
    public function confirm()
    {
        return [
            '确定要忽略这个问题吗？',
            '忽略后将不再提醒，但可以在列表中查看。',
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
        // 获取问题ID
        $questionId = $this->getKey();

        // 查找问题
        $question = AgentQuestion::find($questionId);

        if (!$question) {
            return $this->response()
                ->error('问题不存在');
        }

        // 权限检查
        $user = Auth::guard('user-admin')->user();
        if (!$user || $question->user_id !== $user->id) {
            return $this->response()
                ->error('无权访问此问题');
        }

        if ($question->status !== AgentQuestion::STATUS_PENDING) {
            return $this->response()
                ->error('此问题已经处理过了');
        }

        // 标记为忽略
        $question->status = AgentQuestion::STATUS_IGNORED;
        $question->save();

        return $this->response()
            ->success('问题已忽略')
            ->refresh();
    }

 
    
}
