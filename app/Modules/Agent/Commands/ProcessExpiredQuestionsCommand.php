<?php

namespace App\Modules\Agent\Commands;

use App\Modules\Agent\Services\QuestionService;
use App\Modules\Agent\Services\QuestionNotificationService;
use App\Modules\Agent\Models\AgentQuestion;
use Illuminate\Console\Command;

class ProcessExpiredQuestionsCommand extends Command
{
    protected $signature = 'questions:process-expired 
                           {--dry-run : Show what would be processed without making changes}
                           {--notify-before=30 : Minutes before expiry to send notification}';

    protected $description = 'Process expired questions and send expiry notifications';

    public function __construct(
        private QuestionService $questionService,
        private QuestionNotificationService $notificationService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $notifyBefore = (int) $this->option('notify-before');

        $this->info('Processing expired questions...');

        // 处理即将过期的问题（发送提醒）
        $this->processExpiringQuestions($notifyBefore, $dryRun);

        // 处理已过期的问题
        $this->processExpiredQuestions($dryRun);

        return Command::SUCCESS;
    }

    /**
     * 处理即将过期的问题
     */
    private function processExpiringQuestions(int $minutesBefore, bool $dryRun): void
    {
        $expiringQuestions = AgentQuestion::pending()
            ->whereNotNull('expires_at')
            ->whereBetween('expires_at', [
                now()->addMinutes($minutesBefore - 5), // 5分钟容差
                now()->addMinutes($minutesBefore + 5)
            ])
            ->get();

        if ($expiringQuestions->isEmpty()) {
            $this->info('No questions expiring soon.');
            return;
        }

        $this->info("Found {$expiringQuestions->count()} questions expiring in ~{$minutesBefore} minutes:");

        foreach ($expiringQuestions as $question) {
            $minutesUntilExpiry = now()->diffInMinutes($question->expires_at);
            
            $this->line("  - Question #{$question->id}: {$question->title} (expires in {$minutesUntilExpiry} minutes)");

            if (!$dryRun) {
                $this->notificationService->notifyQuestionExpiring($question, $minutesUntilExpiry);
            }
        }

        if ($dryRun) {
            $this->warn('DRY RUN: No notifications were sent.');
        }
    }

    /**
     * 处理已过期的问题
     */
    private function processExpiredQuestions(bool $dryRun): void
    {
        $expiredQuestions = AgentQuestion::pending()
            ->expired()
            ->get();

        if ($expiredQuestions->isEmpty()) {
            $this->info('No expired questions to process.');
            return;
        }

        $this->info("Found {$expiredQuestions->count()} expired questions:");

        foreach ($expiredQuestions as $question) {
            $expiredMinutes = $question->expires_at->diffInMinutes(now());
            
            $this->line("  - Question #{$question->id}: {$question->title} (expired {$expiredMinutes} minutes ago)");

            if (!$dryRun) {
                // 标记为忽略
                $question->markAsIgnored();
                
                // 发送过期通知
                $this->notificationService->notifyQuestionExpired($question);
            }
        }

        if ($dryRun) {
            $this->warn('DRY RUN: No questions were marked as ignored.');
        } else {
            $processedCount = $this->questionService->processExpiredQuestions();
            $this->info("Processed {$processedCount} expired questions.");
        }
    }
}
