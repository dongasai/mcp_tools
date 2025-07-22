<?php


namespace App\UserAdmin\Actions\Question;

use Dcat\Admin\Widgets\Form;


/**
 * 文本回答表单
 *
 */
class Answer2QuestionForm extends Form
{
    public function handle(array $input)
    {
        // dump($input);

        // return $this->response()->error('Your error message.');

        return $this->response()->success('Processed successfully.')->refresh();
    }


    // 构建表单
    public function form()
    {
        // Since v1.6.5 弹出确认弹窗
        $this->confirm('您确定要提交表单吗', 'content');

        $this->text('name')->required();
        $this->email('email')->rules('email');
    }

    /**
     * 返回表单数据，如不需要可以删除此方法
     *
     * @return array
     */
    public function default()
    {
        return [
            'name'  => 'John Doe',
            'email' => 'John.Doe@gmail.com',
        ];
    }


}
