<?php

namespace App\UserAdmin\Actions\Question;

use App\Modules\Agent\Models\AgentQuestion;
use Dcat\Admin\Actions\Response;
use Dcat\Admin\Grid\RowAction;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AnswerQuestionAction extends RowAction
{
    /**
     * 标题
     *
     * @return string
     */
    public function title()
    {
        return '回答-文本';
    }

    

    public function render()
    {

        if (!$this->allowed()) {
            return '';
        }

        return $this->render2();
    }

    public function render2()
    {   
        
            // Answer2QuestionForm
        // 实例化表单类并传递自定义参数
        $form = $this->ConfigEditForm::make();
        $form->payload([
                           'id'    => $this->getKey(),
                           'value' => $this->getRow()->value1
                       ]);

        return Modal::make()
            ->lg()
            ->title($this->title())
            ->body($form)
            ->button($this->title());
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

        // 验证回答内容
        $validated = $request->validate([
            'answer' => 'required|string',
            'answer_type' => 'sometimes|string|in:TEXT',
        ]);
        return $this->runcall($validated,$user,$question);
    }


    protected function runcall($validated,$user,$question)  {
        
        // 更新问题
        $question->answer = $validated['answer'];
        $question->answer_type = $validated['answer_type'] ?? AgentQuestion::ANSWER_TYPE_TEXT;
        $question->answered_by = $user->id;
        $question->answered_at = now();
        $question->status = AgentQuestion::STATUS_ANSWERED;
        $question->save();

        return $this->response()
            ->success('问题回答成功')
            ->refresh();
    }

    
    


}
