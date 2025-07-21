<?php

namespace App\UserAdmin\Actions\Grid;

use App\Modules\Agent\Models\AgentQuestion;
use Dcat\Admin\Actions\Response;
use Dcat\Admin\Grid\RowAction;
use Dcat\Admin\Widgets\Modal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AnswerQuestionAction extends RowAction
{
    protected $title = '回答';

    public function render()
    {
        // 只有待回答的问题才显示回答按钮
        if ($this->row->status !== AgentQuestion::STATUS_PENDING) {
            return '';
        }

        $this->setHtmlAttribute([
            'data-toggle' => 'modal',
            'data-target' => '#answer-question-modal-' . $this->getKey(),
        ]);

        return <<<HTML
<span class="{$this->getElementClass()}" {$this->formatHtmlAttributes()}>
    <i class="fa fa-reply"></i> {$this->title}
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

        $validated = $request->validate([
            'answer' => 'required|string',
            'answer_type' => 'sometimes|string|in:TEXT,CHOICE,JSON',
        ]);

        // 更新问题
        $question->answer = $validated['answer'];
        $question->answer_type = $validated['answer_type'] ?? AgentQuestion::ANSWER_TYPE_TEXT;
        $question->answered_by = $user->id;
        $question->answered_at = now();
        $question->status = AgentQuestion::STATUS_ANSWERED;
        $question->save();

        return $this->response()->success('问题回答成功')->refresh();
    }

    public function form()
    {
        $question = AgentQuestion::findOrFail($this->getKey());
        
        $form = <<<HTML
<div class="modal fade" id="answer-question-modal-{$this->getKey()}" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">回答问题</h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>问题标题</label>
                    <p class="form-control-static">{$question->title}</p>
                </div>
                <div class="form-group">
                    <label>问题内容</label>
                    <div class="form-control-static" style="max-height: 200px; overflow-y: auto;">
                        {$question->content}
                    </div>
                </div>
HTML;

        if ($question->answer_options && is_array($question->answer_options)) {
            $form .= '<div class="form-group"><label>请选择答案</label>';
            foreach ($question->answer_options as $option) {
                $value = $option['value'] ?? '';
                $label = $option['label'] ?? $value;
                $form .= <<<HTML
                <div class="radio">
                    <label>
                        <input type="radio" name="answer" value="{$value}" required>
                        {$label}
                    </label>
                </div>
HTML;
            }
            $form .= '<input type="hidden" name="answer_type" value="CHOICE"></div>';
        } else {
            $form .= <<<HTML
                <div class="form-group">
                    <label for="answer">回答内容 <span class="text-danger">*</span></label>
                    <textarea name="answer" class="form-control" rows="5" required placeholder="请输入您的回答..."></textarea>
                </div>
                <div class="form-group">
                    <label for="answer_type">回答类型</label>
                    <select name="answer_type" class="form-control">
                        <option value="TEXT">文本</option>
                        <option value="JSON">JSON</option>
                    </select>
                </div>
HTML;
        }

        $form .= <<<HTML
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
                <button type="submit" class="btn btn-primary">提交回答</button>
            </div>
        </div>
    </div>
</div>
HTML;

        return $form;
    }

    protected function setupScript()
    {
        return <<<JS
$('#{$this->getElementSelector()}').on('click', function() {
    var modal = $('#answer-question-modal-{$this->getKey()}');
    if (modal.length === 0) {
        $('body').append(`{$this->form()}`);
        modal = $('#answer-question-modal-{$this->getKey()}');
    }
    
    modal.find('form').off('submit').on('submit', function(e) {
        e.preventDefault();
        var form = $(this);
        var formData = new FormData(form[0]);
        
        $.ajax({
            url: '{$this->getHandleRoute()}',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.status) {
                    Dcat.success(response.message || '操作成功');
                    modal.modal('hide');
                    Dcat.reload();
                } else {
                    Dcat.error(response.message || '操作失败');
                }
            },
            error: function(xhr) {
                var response = xhr.responseJSON;
                Dcat.error(response?.message || '操作失败');
            }
        });
    });
    
    modal.modal('show');
});
JS;
    }
}
