<?php

namespace App\Modules\Task\Listeners;

use App\Modules\Task\Events\TaskProgressUpdated;
use Illuminate\Support\Facades\Log;

class HandleTaskProgressUpdate
{
    /**
     * 处理任务进度更新事件
     */
    public function handle(TaskProgressUpdated $event): void
    {
        $task = $event->task;
        $oldProgress = $event->previousProgress;

        // 记录进度更新日志
        Log::info('Task progress updated', [
            'task_id' => $task->id,
            'title' => $task->title,
            'old_progress' => $oldProgress,
            'new_progress' => $task->progress,
            'user_id' => $task->user_id,
        ]);

        // 检查是否启用进度更新通知
        if (config('task.notifications.progress_updated', false)) {
            $this->sendProgressUpdateNotification($task, $oldProgress);
        }

        // 自动更新父任务进度
        if (config('task.automation.auto_update_progress', true)) {
            $this->updateParentTaskProgress($task);
        }

        // 检查里程碑进度
        $this->checkProgressMilestones($task, $oldProgress);

        // 更新项目统计
        $this->updateProjectStatistics($task);
    }

    /**
     * 发送进度更新通知
     */
    private function sendProgressUpdateNotification($task, $oldProgress): void
    {
        // 只在重要进度节点发送通知（25%, 50%, 75%, 100%）
        $milestones = [25, 50, 75, 100];
        $currentMilestone = null;
        $previousMilestone = null;

        foreach ($milestones as $milestone) {
            if ($task->progress >= $milestone && $oldProgress < $milestone) {
                $currentMilestone = $milestone;
                break;
            }
        }

        if ($currentMilestone) {
            Log::debug('Sending progress milestone notification', [
                'task_id' => $task->id,
                'milestone' => $currentMilestone,
                'progress' => $task->progress,
            ]);

            // TODO: 实现通知逻辑
        }
    }

    /**
     * 更新父任务进度
     */
    private function updateParentTaskProgress($task): void
    {
        if (!$task->parentTask) {
            return;
        }

        $parentTask = $task->parentTask;
        $subTasks = $parentTask->subTasks;

        if ($subTasks->isEmpty()) {
            return;
        }

        // 计算所有子任务的平均进度
        $totalProgress = $subTasks->sum('progress');
        $averageProgress = round($totalProgress / $subTasks->count());

        // 更新父任务进度
        if ($parentTask->progress !== $averageProgress) {
            $parentTask->update(['progress' => $averageProgress]);
            
            Log::debug('Parent task progress updated', [
                'parent_task_id' => $parentTask->id,
                'new_progress' => $averageProgress,
                'sub_tasks_count' => $subTasks->count(),
            ]);
        }
    }

    /**
     * 检查进度里程碑
     */
    private function checkProgressMilestones($task, $oldProgress): void
    {
        $milestones = [
            25 => 'quarter_complete',
            50 => 'half_complete', 
            75 => 'three_quarters_complete',
            100 => 'fully_complete',
        ];

        foreach ($milestones as $threshold => $milestone) {
            if ($task->progress >= $threshold && $oldProgress < $threshold) {
                Log::info("Task reached milestone: {$milestone}", [
                    'task_id' => $task->id,
                    'milestone' => $milestone,
                    'progress' => $task->progress,
                ]);

                // 触发里程碑事件
                $this->triggerMilestoneEvent($task, $milestone, $threshold);
            }
        }
    }

    /**
     * 触发里程碑事件
     */
    private function triggerMilestoneEvent($task, $milestone, $threshold): void
    {
        // TODO: 可以创建专门的里程碑事件
        // event(new TaskMilestoneReached($task, $milestone, $threshold));
        
        Log::debug('Milestone event triggered', [
            'task_id' => $task->id,
            'milestone' => $milestone,
            'threshold' => $threshold,
        ]);
    }

    /**
     * 更新项目统计
     */
    private function updateProjectStatistics($task): void
    {
        if (!$task->project_id) {
            return;
        }

        // TODO: 更新项目级别的进度统计
        Log::debug('Project statistics updated for progress change', [
            'task_id' => $task->id,
            'project_id' => $task->project_id,
            'task_progress' => $task->progress,
        ]);
    }
}
