<?php

namespace App\Modules\Task\Commands;

use Illuminate\Console\Command;
use App\Modules\Task\Models\Task;
use App\Modules\Task\Services\TaskWorkflowService;
use App\Modules\Task\Enums\TASKSTATUS;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * 任务工作流定时调度命令
 * 
 * 处理基于时间的自动任务流转，如：
 * - 超时任务自动阻塞
 * - 长时间未更新的任务提醒
 * - 定期检查工作流健康状态
 */
class TaskWorkflowScheduleCommand extends Command
{
    /**
     * 命令签名
     */
    protected $signature = 'task:workflow-schedule 
                            {--type=all : 执行类型 (all|timeout|health|reminder)}
                            {--dry-run : 仅显示将要执行的操作，不实际执行}';

    /**
     * 命令描述
     */
    protected $description = '执行任务工作流定时调度，处理超时、健康检查等自动化流程';

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
        $type = $this->option('type');
        $dryRun = $this->option('dry-run');

        $this->info("开始执行任务工作流定时调度 (类型: {$type}, 模拟运行: " . ($dryRun ? '是' : '否') . ")");

        try {
            match ($type) {
                'timeout' => $this->handleTimeoutTasks($dryRun),
                'health' => $this->checkWorkflowHealth($dryRun),
                'reminder' => $this->sendReminders($dryRun),
                'all' => $this->handleAll($dryRun),
                default => throw new \InvalidArgumentException("未知的执行类型: {$type}"),
            };

            $this->info('任务工作流定时调度执行完成');
            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error("执行失败: {$e->getMessage()}");
            Log::error('Task workflow schedule failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return self::FAILURE;
        }
    }

    /**
     * 执行所有类型的调度
     */
    private function handleAll(bool $dryRun): void
    {
        $this->handleTimeoutTasks($dryRun);
        $this->checkWorkflowHealth($dryRun);
        $this->sendReminders($dryRun);
    }

    /**
     * 处理超时任务
     */
    private function handleTimeoutTasks(bool $dryRun): void
    {
        $this->info('检查超时任务...');

        // 获取配置的超时时间
        $timeoutHours = config('task.automation.timeout_hours', 72); // 默认72小时
        $timeoutDate = Carbon::now()->subHours($timeoutHours);

        // 查找超时的进行中任务
        $timeoutTasks = Task::where('status', TASKSTATUS::IN_PROGRESS->value)
            ->where('updated_at', '<', $timeoutDate)
            ->whereNull('due_date') // 没有明确截止日期的任务
            ->get();

        $this->info("发现 {$timeoutTasks->count()} 个超时任务");

        foreach ($timeoutTasks as $task) {
            $this->line("- 任务 #{$task->id}: {$task->title} (最后更新: {$task->updated_at})");

            if (!$dryRun) {
                // 检查是否可以转换为阻塞状态
                if ($this->workflowService->canTransition($task, TASKSTATUS::BLOCKED)) {
                    $success = $this->workflowService->transition($task, TASKSTATUS::BLOCKED, [
                        'auto_blocked' => true,
                        'reason' => "任务超时 {$timeoutHours} 小时未更新",
                        'scheduled_by' => 'workflow-schedule',
                    ]);

                    if ($success) {
                        $this->info("  ✓ 已自动阻塞");
                        Log::info('Task auto-blocked due to timeout', [
                            'task_id' => $task->id,
                            'timeout_hours' => $timeoutHours,
                        ]);
                    } else {
                        $this->warn("  ✗ 阻塞失败");
                    }
                } else {
                    $this->warn("  ✗ 无法阻塞（不符合转换规则）");
                }
            }
        }
    }

    /**
     * 检查工作流健康状态
     */
    private function checkWorkflowHealth(bool $dryRun): void
    {
        $this->info('检查工作流健康状态...');

        // 获取所有活跃任务
        $activeTasks = Task::whereIn('status', [
            TASKSTATUS::PENDING->value,
            TASKSTATUS::IN_PROGRESS->value,
            TASKSTATUS::BLOCKED->value,
            TASKSTATUS::ON_HOLD->value,
        ])->get();

        $unhealthyTasks = [];

        foreach ($activeTasks as $task) {
            $health = $this->workflowService->checkWorkflowHealth($task);
            
            if (!$health['is_healthy']) {
                $unhealthyTasks[] = [
                    'task' => $task,
                    'health' => $health,
                ];
            }
        }

        $this->info("检查了 {$activeTasks->count()} 个活跃任务，发现 " . count($unhealthyTasks) . " 个不健康的任务");

        foreach ($unhealthyTasks as $item) {
            $task = $item['task'];
            $health = $item['health'];

            $this->warn("任务 #{$task->id}: {$task->title}");
            foreach ($health['issues'] as $issue) {
                $this->line("  问题: {$issue}");
            }
            foreach ($health['recommendations'] as $recommendation) {
                $this->line("  建议: {$recommendation}");
            }

            if (!$dryRun) {
                // 记录健康检查结果
                Log::warning('Task workflow health issue detected', [
                    'task_id' => $task->id,
                    'issues' => $health['issues'],
                    'recommendations' => $health['recommendations'],
                ]);
            }
        }
    }

    /**
     * 发送提醒
     */
    private function sendReminders(bool $dryRun): void
    {
        $this->info('检查需要提醒的任务...');

        $reminderDays = config('task.automation.reminder_days', 7); // 默认7天
        $reminderDate = Carbon::now()->subDays($reminderDays);

        // 查找长时间未更新的待处理任务
        $staleTasks = Task::where('status', TASKSTATUS::PENDING->value)
            ->where('updated_at', '<', $reminderDate)
            ->get();

        // 查找即将到期的任务
        $dueSoonTasks = Task::whereIn('status', [
            TASKSTATUS::PENDING->value,
            TASKSTATUS::IN_PROGRESS->value,
        ])
            ->whereNotNull('due_date')
            ->where('due_date', '<=', Carbon::now()->addDays(1))
            ->where('due_date', '>', Carbon::now())
            ->get();

        $this->info("发现 {$staleTasks->count()} 个长时间未更新的任务");
        $this->info("发现 {$dueSoonTasks->count()} 个即将到期的任务");

        // 处理长时间未更新的任务
        foreach ($staleTasks as $task) {
            $this->line("- 待处理任务 #{$task->id}: {$task->title} (已等待 {$reminderDays} 天)");

            if (!$dryRun) {
                Log::info('Stale task reminder', [
                    'task_id' => $task->id,
                    'days_stale' => $reminderDays,
                ]);
                // TODO: 发送实际的提醒通知（邮件、短信等）
            }
        }

        // 处理即将到期的任务
        foreach ($dueSoonTasks as $task) {
            $hoursUntilDue = Carbon::now()->diffInHours($task->due_date);
            $this->line("- 即将到期任务 #{$task->id}: {$task->title} (还有 {$hoursUntilDue} 小时)");

            if (!$dryRun) {
                Log::info('Task due soon reminder', [
                    'task_id' => $task->id,
                    'hours_until_due' => $hoursUntilDue,
                ]);
                // TODO: 发送实际的提醒通知（邮件、短信等）
            }
        }
    }
}
