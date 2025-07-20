<?php

namespace App\Modules\Agent\Services;

use App\Modules\Agent\Models\AgentQuestion;
use App\Modules\User\Models\User;
use App\Modules\Core\Contracts\LogInterface;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

class QuestionNotificationService
{
    public function __construct(
        private LogInterface $logger
    ) {}

    /**
     * 通知用户有新问题
     */
    public function notifyNewQuestion(AgentQuestion $question): bool
    {
        try {
            $user = $question->user;
            if (!$user) {
                $this->logger->warning('Cannot notify: user not found', [
                    'question_id' => $question->id,
                ]);
                return false;
            }

            // 发送实时通知事件
            $this->sendRealtimeNotification($question, 'new_question');

            // 根据优先级决定通知方式
            $notificationMethods = $this->getNotificationMethods($question->priority);

            foreach ($notificationMethods as $method) {
                $this->sendNotification($question, $user, $method, 'new_question');
            }

            $this->logger->info('New question notification sent', [
                'question_id' => $question->id,
                'user_id' => $user->id,
                'priority' => $question->priority,
                'methods' => $notificationMethods,
            ]);

            return true;

        } catch (\Exception $e) {
            $this->logger->error('Failed to send new question notification', [
                'question_id' => $question->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * 通知Agent问题已被回答
     */
    public function notifyQuestionAnswered(AgentQuestion $question): bool
    {
        try {
            $agent = $question->agent;
            if (!$agent) {
                $this->logger->warning('Cannot notify: agent not found', [
                    'question_id' => $question->id,
                ]);
                return false;
            }

            // 发送实时通知事件
            $this->sendRealtimeNotification($question, 'question_answered');

            // 记录Agent通知
            $this->logger->info('Question answered notification sent', [
                'question_id' => $question->id,
                'agent_id' => $agent->id,
                'answered_by' => $question->answered_by,
            ]);

            return true;

        } catch (\Exception $e) {
            $this->logger->error('Failed to send question answered notification', [
                'question_id' => $question->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * 通知问题即将过期
     */
    public function notifyQuestionExpiring(AgentQuestion $question, int $minutesUntilExpiry): bool
    {
        try {
            $user = $question->user;
            if (!$user) {
                return false;
            }

            // 只对高优先级和紧急问题发送过期提醒
            if (!in_array($question->priority, [AgentQuestion::PRIORITY_URGENT, AgentQuestion::PRIORITY_HIGH])) {
                return true;
            }

            // 发送实时通知事件
            $this->sendRealtimeNotification($question, 'question_expiring', [
                'minutes_until_expiry' => $minutesUntilExpiry,
            ]);

            $this->logger->info('Question expiring notification sent', [
                'question_id' => $question->id,
                'user_id' => $user->id,
                'minutes_until_expiry' => $minutesUntilExpiry,
            ]);

            return true;

        } catch (\Exception $e) {
            $this->logger->error('Failed to send question expiring notification', [
                'question_id' => $question->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * 通知问题已过期
     */
    public function notifyQuestionExpired(AgentQuestion $question): bool
    {
        try {
            $user = $question->user;
            $agent = $question->agent;

            if ($user) {
                $this->sendRealtimeNotification($question, 'question_expired');
            }

            $this->logger->info('Question expired notification sent', [
                'question_id' => $question->id,
                'user_id' => $user?->id,
                'agent_id' => $agent?->id,
            ]);

            return true;

        } catch (\Exception $e) {
            $this->logger->error('Failed to send question expired notification', [
                'question_id' => $question->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * 批量通知待回答问题
     */
    public function notifyPendingQuestions(User $user, int $limit = 10): int
    {
        try {
            $pendingQuestions = AgentQuestion::pending()
                ->forUser($user->id)
                ->notExpired()
                ->byPriority()
                ->limit($limit)
                ->get();

            if ($pendingQuestions->isEmpty()) {
                return 0;
            }

            // 发送批量通知
            $this->sendRealtimeNotification(null, 'pending_questions_summary', [
                'user_id' => $user->id,
                'count' => $pendingQuestions->count(),
                'questions' => $pendingQuestions->map(function ($question) {
                    return [
                        'id' => $question->id,
                        'title' => $question->title,
                        'priority' => $question->priority,
                        'question_type' => $question->question_type,
                        'created_at' => $question->created_at->toISOString(),
                        'expires_at' => $question->expires_at?->toISOString(),
                    ];
                })->toArray(),
            ]);

            $this->logger->info('Pending questions summary sent', [
                'user_id' => $user->id,
                'count' => $pendingQuestions->count(),
            ]);

            return $pendingQuestions->count();

        } catch (\Exception $e) {
            $this->logger->error('Failed to send pending questions notification', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            return 0;
        }
    }

    /**
     * 根据优先级获取通知方式
     */
    private function getNotificationMethods(string $priority): array
    {
        return match($priority) {
            AgentQuestion::PRIORITY_URGENT => ['realtime', 'email', 'push'],
            AgentQuestion::PRIORITY_HIGH => ['realtime', 'email'],
            AgentQuestion::PRIORITY_MEDIUM => ['realtime'],
            AgentQuestion::PRIORITY_LOW => ['realtime'],
            default => ['realtime'],
        };
    }

    /**
     * 发送实时通知
     */
    private function sendRealtimeNotification(?AgentQuestion $question, string $type, array $data = []): void
    {
        $eventData = [
            'type' => $type,
            'timestamp' => now()->toISOString(),
            'data' => $data,
        ];

        if ($question) {
            $eventData['question'] = [
                'id' => $question->id,
                'title' => $question->title,
                'priority' => $question->priority,
                'question_type' => $question->question_type,
                'status' => $question->status,
                'user_id' => $question->user_id,
                'agent_id' => $question->agent_id,
            ];
        }

        // 触发实时事件（可以通过WebSocket、SSE等方式推送）
        Event::dispatch('question.notification', $eventData);
    }

    /**
     * 发送通知
     */
    private function sendNotification(AgentQuestion $question, User $user, string $method, string $type): void
    {
        switch ($method) {
            case 'email':
                $this->sendEmailNotification($question, $user, $type);
                break;
            case 'push':
                $this->sendPushNotification($question, $user, $type);
                break;
            case 'realtime':
                // 实时通知已在上面处理
                break;
        }
    }

    /**
     * 发送邮件通知
     */
    private function sendEmailNotification(AgentQuestion $question, User $user, string $type): void
    {
        // 这里可以实现邮件通知逻辑
        // 暂时只记录日志
        $this->logger->info('Email notification would be sent', [
            'question_id' => $question->id,
            'user_email' => $user->email,
            'type' => $type,
        ]);
    }

    /**
     * 发送推送通知
     */
    private function sendPushNotification(AgentQuestion $question, User $user, string $type): void
    {
        // 这里可以实现推送通知逻辑
        // 暂时只记录日志
        $this->logger->info('Push notification would be sent', [
            'question_id' => $question->id,
            'user_id' => $user->id,
            'type' => $type,
        ]);
    }
}
