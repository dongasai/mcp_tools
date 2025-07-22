<?php

namespace App\UserAdmin\Actions\Question;

use App\Modules\Agent\Models\AgentQuestion;
use Dcat\Admin\Widgets\Form;
use Illuminate\Support\Facades\Auth;

/**
 * 文本回答表单
 */
class Answer2QuestionForm extends Form
{
    protected $questionId;

    public function __construct($questionId = null)
    {
        $this->questionId = $questionId;
        parent::__construct();
    }

    public static function makeWithQuestionId($questionId = null)
    {
        return new static($questionId);
    }

    public function handle(array $input)
    {
        // 获取问题ID
        $questionId = $input['question_id'] ?? $this->questionId;

        // 查找问题
        $question = AgentQuestion::find($questionId);

        if (!$question) {
            return $this->response()->error('问题不存在');
        }

        // 权限检查
        $user = Auth::guard('user-admin')->user();
        if (!$user || $question->user_id !== $user->id) {
            return $this->response()->error('无权访问此问题');
        }

        if ($question->status !== AgentQuestion::STATUS_PENDING) {
            return $this->response()->error('此问题已经处理过了');
        }

        // 验证回答内容
        if (empty($input['answer'])) {
            return $this->response()->error('回答内容不能为空');
        }

        $validated = [
            'answer' => $input['answer'],
            'answer_type' => $input['answer_type'] ?? 'TEXT',
        ];

        // 更新问题
        $question->answer = $validated['answer'];
        $question->answer_type = $validated['answer_type'] ?? AgentQuestion::ANSWER_TYPE_TEXT;
        $question->answered_by = $user->id;
        $question->answered_at = now();
        $question->status = AgentQuestion::STATUS_ANSWERED;
        $question->save();

        return $this->response()->success('问题回答成功')->refresh();
    }

    // 构建表单
    public function form()
    {
        // 获取问题信息
        if ($this->questionId) {
            $question = AgentQuestion::find($this->questionId);
            if ($question) {
                $this->display('question_content', '问题内容')->value($question->content);
            }
        }

        $this->textarea('answer', '回答内容')->required()->rows(4)->placeholder('请输入您的回答...');
        $this->hidden('answer_type')->value('TEXT');
        $this->hidden('question_id')->value($this->questionId);
    }

    /**
     * 返回表单数据，如不需要可以删除此方法
     *
     * @return array
     */
    public function default()
    {
        return [
            'answer' => '',
            'answer_type' => 'TEXT',
            'question_id' => $this->questionId,
        ];
    }
}
