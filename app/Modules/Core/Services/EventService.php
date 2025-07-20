<?php

namespace App\Modules\Core\Services;

use App\Modules\Core\Contracts\EventInterface;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Log;

class EventService implements EventInterface
{
    protected array $listeners = [];
    protected bool $asyncEnabled;
    protected string $queueConnection;

    public function __construct()
    {
        $this->asyncEnabled = config('core.events.async_enabled', true);
        $this->queueConnection = config('core.events.queue_connection', 'database');
    }

    /**
     * 分发事件
     */
    public function dispatch(object $event): void
    {
        try {
            Event::dispatch($event);
            
            $this->logEvent('dispatched', $event);
        } catch (\Exception $e) {
            Log::error('Event dispatch failed', [
                'event' => get_class($event),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * 监听事件
     */
    public function listen(string $event, callable $listener): void
    {
        Event::listen($event, $listener);
        
        if (!isset($this->listeners[$event])) {
            $this->listeners[$event] = [];
        }
        
        $this->listeners[$event][] = $listener;
        
        Log::debug('Event listener registered', [
            'event' => $event,
            'listener_count' => count($this->listeners[$event]),
        ]);
    }

    /**
     * 订阅事件
     */
    public function subscribe(string $subscriber): void
    {
        Event::subscribe($subscriber);
        
        Log::debug('Event subscriber registered', [
            'subscriber' => $subscriber,
        ]);
    }

    /**
     * 异步分发事件
     */
    public function dispatchAsync(object $event): void
    {
        if (!$this->asyncEnabled) {
            $this->dispatch($event);
            return;
        }

        try {
            // 创建异步事件任务
            $job = new \App\Modules\Core\Jobs\AsyncEventJob($event);
            Queue::connection($this->queueConnection)->push($job);
            
            $this->logEvent('dispatched_async', $event);
        } catch (\Exception $e) {
            Log::error('Async event dispatch failed', [
                'event' => get_class($event),
                'error' => $e->getMessage(),
            ]);
            
            // 降级到同步处理
            $this->dispatch($event);
        }
    }

    /**
     * 批量分发事件
     */
    public function dispatchBatch(array $events): void
    {
        $successful = 0;
        $failed = 0;
        
        foreach ($events as $event) {
            try {
                $this->dispatch($event);
                $successful++;
            } catch (\Exception $e) {
                $failed++;
                Log::error('Batch event dispatch failed', [
                    'event' => get_class($event),
                    'error' => $e->getMessage(),
                ]);
            }
        }
        
        Log::info('Batch event dispatch completed', [
            'total' => count($events),
            'successful' => $successful,
            'failed' => $failed,
        ]);
    }

    /**
     * 分发事件直到返回非null值
     */
    public function until(object $event): mixed
    {
        try {
            $result = Event::until($event);
            
            $this->logEvent('dispatched_until', $event, ['result' => $result]);
            
            return $result;
        } catch (\Exception $e) {
            Log::error('Event until dispatch failed', [
                'event' => get_class($event),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * 忘记事件监听器
     */
    public function forget(string $event): void
    {
        Event::forget($event);
        
        if (isset($this->listeners[$event])) {
            unset($this->listeners[$event]);
        }
        
        Log::debug('Event listeners forgotten', ['event' => $event]);
    }

    /**
     * 检查是否有监听器
     */
    public function hasListeners(string $event): bool
    {
        return Event::hasListeners($event);
    }

    /**
     * 获取事件监听器
     */
    public function getListeners(string $event): array
    {
        return Event::getListeners($event);
    }

    /**
     * 记录事件日志
     */
    protected function logEvent(string $action, object $event, array $extra = []): void
    {
        $context = array_merge([
            'action' => $action,
            'event_class' => get_class($event),
            'event_data' => $this->serializeEvent($event),
            'timestamp' => microtime(true),
        ], $extra);

        Log::info("Event {$action}", $context);
    }

    /**
     * 序列化事件对象
     */
    protected function serializeEvent(object $event): array
    {
        try {
            // 获取事件的公共属性
            $reflection = new \ReflectionClass($event);
            $properties = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);
            
            $data = [];
            foreach ($properties as $property) {
                $data[$property->getName()] = $property->getValue($event);
            }
            
            return $data;
        } catch (\Exception $e) {
            return ['serialization_error' => $e->getMessage()];
        }
    }

    /**
     * 获取事件统计
     */
    public function getStats(): array
    {
        return [
            'registered_listeners' => array_map('count', $this->listeners),
            'total_listeners' => array_sum(array_map('count', $this->listeners)),
            'async_enabled' => $this->asyncEnabled,
            'queue_connection' => $this->queueConnection,
        ];
    }

    /**
     * 清理事件监听器
     */
    public function cleanup(): void
    {
        $this->listeners = [];
        Event::flush();
        
        Log::info('Event service cleaned up');
    }

    /**
     * 重试失败的异步事件
     */
    public function retryFailedEvents(): int
    {
        try {
            // 这里可以实现重试逻辑
            // 例如从失败队列中获取事件并重新处理
            $retryCount = 0;
            
            Log::info('Failed events retry completed', [
                'retry_count' => $retryCount,
            ]);
            
            return $retryCount;
        } catch (\Exception $e) {
            Log::error('Failed events retry error', [
                'error' => $e->getMessage(),
            ]);
            return 0;
        }
    }
}
