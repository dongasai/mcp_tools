<?php

namespace App\Admin\Actions\Grid;

use Modules\Agent\Models\AgentQuestion;
use Dcat\Admin\Actions\Response;
use Dcat\Admin\Grid\BatchAction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class BatchIgnoreQuestions extends BatchAction
{
    protected $title = '标记为已忽略';

    public function handle(Request $request): Response
    {
        // 获取选中的问题ID
        $keys = $this->getKey();
        
        // 只处理待回答状态的问题
        $questions = AgentQuestion::whereIn('id', $keys)
            ->where('status', AgentQuestion::STATUS_PENDING)
            ->get();

        if ($questions->isEmpty()) {
            return $this->response()->error('没有找到可以忽略的待回答问题');
        }

        // 批量更新状态
        $updatedCount = AgentQuestion::whereIn('id', $questions->pluck('id'))
            ->update(['status' => AgentQuestion::STATUS_IGNORED]);

        return $this->response()->success("成功忽略了 {$updatedCount} 个问题")->refresh();
    }

    /**
     * 设置确认信息
     */
    public function confirm()
    {
        return ['确定要将选中的问题标记为已忽略吗？', '此操作不可撤销'];
    }
}
