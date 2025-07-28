<?php

namespace Modules\UserAdmin\Actions\Grid;

use Modules\MCP\Models\AgentQuestion;
use Dcat\Admin\Actions\Response;
use Dcat\Admin\Grid\RowAction;
use Dcat\Admin\Widgets\Modal;
use App\UserAdmin\Actions\Question\Answer2QuestionForm;
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
        return '回答';
    }

    /**
     * 渲染动作
     *
     * @return string
     */
    public function render()
    {
        if (!$this->allowed()) {
            return '';
        }

        $form = Answer2QuestionForm::makeWithQuestionId($this->getKey());

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

        return $this->processAnswer($validated, $user, $question);
    }

    /**
     * 处理回答逻辑
     *
     * @param array $validated
     * @param mixed $user
     * @param AgentQuestion $question
     * @return Response
     */
    protected function processAnswer(array $validated, $user, AgentQuestion $question)
    {
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