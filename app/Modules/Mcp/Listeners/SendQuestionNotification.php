<?php

namespace App\Modules\Mcp\Listeners;

use App\Modules\Mcp\Events\QuestionCreated;
use App\Modules\Mcp\Events\QuestionAnswered;
use App\Modules\Mcp\Events\QuestionIgnored;
use App\Modules\Agent\Services\QuestionNotificationService;
use App\Modules\Core\Contracts\LogInterface;

class SendQuestionNotification
{
    public function __construct(
        private QuestionNotificationService $notificationService,
        private LogInterface $logger
    ) {}

    /**
     * 处理问题创建事件
     */
    public function handleQuestionCreated(QuestionCreated $event): void
    {
        $this->notificationService->notifyNewQuestion($event->question);
    }

    /**
     * 处理问题回答事件
     */
    public function handleQuestionAnswered(QuestionAnswered $event): void
    {
        $this->notificationService->notifyQuestionAnswered($event->question);
    }

    /**
     * 处理问题忽略事件
     */
    public function handleQuestionIgnored(QuestionIgnored $event): void
    {
        $this->logger->info('Question ignored', [
            'question_id' => $event->question->id,
            'agent_id' => $event->question->agent_id,
            'user_id' => $event->question->user_id,
        ]);
    }

    /**
     * 注册监听器订阅的事件
     */
    public function subscribe($events): void
    {
        $events->listen(
            QuestionCreated::class,
            [SendQuestionNotification::class, 'handleQuestionCreated']
        );

        $events->listen(
            QuestionAnswered::class,
            [SendQuestionNotification::class, 'handleQuestionAnswered']
        );

        $events->listen(
            QuestionIgnored::class,
            [SendQuestionNotification::class, 'handleQuestionIgnored']
        );
    }
}
