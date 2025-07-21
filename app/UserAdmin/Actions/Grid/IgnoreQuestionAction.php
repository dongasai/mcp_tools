<?php

namespace App\UserAdmin\Actions\Grid;

use App\Modules\Agent\Models\AgentQuestion;
use Dcat\Admin\Actions\Response;
use Dcat\Admin\Grid\RowAction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IgnoreQuestionAction extends RowAction
{
    protected $title = '忽略';

    public function render()
    {
        // 只有待回答的问题才显示忽略按钮
        if ($this->row->status !== AgentQuestion::STATUS_PENDING) {
            return '';
        }

        return <<<HTML
<span class="{$this->getElementClass()}" {$this->formatHtmlAttributes()}>
    <i class="fa fa-times"></i> {$this->title}
</span>
HTML;
    }

    public function handle(Request $request): Response
    {
        $questionId = $this->getKey();
        $question = AgentQuestion::findOrFail($questionId);
        
        // 权限检查
        $user = Auth::guard('user-admin')->user();
        if (!$user || $question->user_id !== $user->id) {
            return $this->response()->error('无权访问此问题');
        }

        if ($question->status !== AgentQuestion::STATUS_PENDING) {
            return $this->response()->error('此问题已经处理过了');
        }

        // 标记为忽略
        $question->status = AgentQuestion::STATUS_IGNORED;
        $question->save();

        return $this->response()->success('问题已忽略')->refresh();
    }

    /**
     * 确认对话框
     */
    public function confirm()
    {
        return ['确定要忽略这个问题吗？', '忽略后将不再提醒，但可以在列表中查看。'];
    }
}
