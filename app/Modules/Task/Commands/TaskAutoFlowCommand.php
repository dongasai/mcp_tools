<?php

namespace App\Modules\Task\Commands;

use Illuminate\Console\Command;
use App\Modules\Task\Models\Task;
use App\Modules\Task\Services\TaskWorkflowService;
use App\Modules\Task\Enums\TASKSTATUS;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * 任务自动流转命令
 * 
 * 简化版的自动流转处理，专注于核心的自动化流程
 */
class TaskAutoFlowCommand extends Command
{
    /**
     * 命令签名
     */
    protected $signature = 'task:auto-flow 
                            {--timeout=72 : 超时小时数，超过此时间的进行中任务将被自动阻塞}
                            {--dry-run : 仅显示将要执行的操作，不实际执行}';

    /**
     * 命令描述
     */
    protected $description = '执行任务自动流转，处理超时任务和父子任务关系';

    /**
     * 工作流服务
     */
    protected TaskWorkflowService $workflowService;

    /**
     * 构造函数
     */
    public function __construct(TaskWorkflowService $workflowService)
    {
        parent::__construct();
        $this->workflowService = $workflowService;
    }

    /**
     * 执行命令
     */
    public function handle(): int
    {
        $timeoutHours = (int) $this->option('timeout');
        $dryRun = $this->option('dry-run');

        $this->info("开始执行任务自动流转 (超时: {$timeoutHours}小时, 模拟: " . ($dryRun ? '是' : '否') . ")");

        try {
            $results = [
                'timeout_blocked' => $this->handleTimeoutTasks($timeoutHours, $dryRun),
                'parent_completed' => $this->handleParentTaskCompletion($dryRun),
                'sub_started' => $this->handleSubTaskAutoStart($dryRun),
            ];

            $this->displayResults($results);
            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error("执行失败: {$e->getMessage()}");
            Log::error('Task auto-flow failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return self::FAILURE;
        }
    }

    /**
     * 处理超时任务
     */
    private function handleTimeoutTasks(int $timeoutHours, bool $dryRun): int
    {
        $timeoutDate = Carbon::now()->subHours($timeoutHours);
        
        $timeoutTasks = Task::where('status', TASKSTATUS::IN_PROGRESS->value)
            ->where('updated_at', '<', $timeoutDate)
            ->get();

        $blockedCount = 0;

        foreach ($timeoutTasks as $task) {
            if ($this->workflowService->canTransition($task, TASKSTATUS::BLOCKED)) {
                if (!$dryRun) {
                    $success = $this->workflowService->transition($task, TASKSTATUS::BLOCKED, [
                        'auto_blocked' => true,
                        'reason' => "任务超时 {$timeoutHours} 小时未更新",
                        'auto_flow_command' => true,
                    ]);

                    if ($success) {
                        $blockedCount++;
                        Log::info('Task auto-blocked by auto-flow', [
                            'task_id' => $task->id,
                            'timeout_hours' => $timeoutHours,
                        ]);
                    }
                } else {
                    $blockedCount++;
                    $this->line("  [模拟] 将阻塞任务 #{$task->id}: {$task->title}");
                }
            }
        }

        return $blockedCount;
    }

    /**
     * 处理父任务自动完成
     */
    private function handleParentTaskCompletion(bool $dryRun): int
    {
        // 查找所有有子任务的主任务
        $parentTasks = Task::where('type', 'main')
            ->whereIn('status', [TASKSTATUS::PENDING->value, TASKSTATUS::IN_PROGRESS->value])
            ->whereHas('subTasks')
            ->get();

        $completedCount = 0;

        foreach ($parentTasks as $parentTask) {
            // 检查是否所有子任务都已完成
            if ($parentTask->areAllSubTasksCompleted()) {
                if ($this->workflowService->canTransition($parentTask, TASKSTATUS::COMPLETED)) {
                    if (!$dryRun) {
                        $success = $this->workflowService->transition($parentTask, TASKSTATUS::COMPLETED, [
                            'auto_completed' => true,
                            'reason' => '所有子任务已完成',
                            'auto_flow_command' => true,
                        ]);

                        if ($success) {
                            $completedCount++;
                            Log::info('Parent task auto-completed by auto-flow', [
                                'task_id' => $parentTask->id,
                            ]);
                        }
                    } else {
                        $completedCount++;
                        $this->line("  [模拟] 将完成父任务 #{$parentTask->id}: {$parentTask->title}");
                    }
                }
            }
        }

        return $completedCount;
    }

    /**
     * 处理子任务自动开始
     */
    private function handleSubTaskAutoStart(bool $dryRun): int
    {
        // 只有在配置启用时才执行
        if (!config('task.automation.auto_start_sub_tasks', false)) {
            return 0;
        }

        // 查找进行中的主任务
        $activeParentTasks = Task::where('type', 'main')
            ->where('status', TASKSTATUS::IN_PROGRESS->value)
            ->whereHas('subTasks', function ($query) {
                $query->where('status', TASKSTATUS::PENDING->value);
            })
            ->get();

        $startedCount = 0;

        foreach ($activeParentTasks as $parentTask) {
            $pendingSubTasks = $parentTask->subTasks()
                ->where('status', TASKSTATUS::PENDING->value)
                ->get();

            foreach ($pendingSubTasks as $subTask) {
                if ($this->workflowService->canTransition($subTask, TASKSTATUS::IN_PROGRESS)) {
                    if (!$dryRun) {
                        $success = $this->workflowService->transition($subTask, TASKSTATUS::IN_PROGRESS, [
                            'auto_started' => true,
                            'reason' => '父任务进行中，自动开始子任务',
                            'auto_flow_command' => true,
                        ]);

                        if ($success) {
                            $startedCount++;
                            Log::info('Sub task auto-started by auto-flow', [
                                'task_id' => $subTask->id,
                                'parent_task_id' => $parentTask->id,
                            ]);
                        }
                    } else {
                        $startedCount++;
                        $this->line("  [模拟] 将开始子任务 #{$subTask->id}: {$subTask->title}");
                    }
                }
            }
        }

        return $startedCount;
    }

    /**
     * 显示执行结果
     */
    private function displayResults(array $results): void
    {
        $this->info('任务自动流转执行完成');
        $this->table(
            ['操作类型', '处理数量'],
            [
                ['超时任务阻塞', $results['timeout_blocked']],
                ['父任务自动完成', $results['parent_completed']],
                ['子任务自动开始', $results['sub_started']],
            ]
        );

        $total = array_sum($results);
        if ($total > 0) {
            $this->info("总共处理了 {$total} 个任务");
        } else {
            $this->info('没有需要处理的任务');
        }
    }
}
