<?php

namespace App\Modules\Core\Contracts;

interface EventInterface
{
    /**
     * 分发事件
     */
    public function dispatch(object $event): void;

    /**
     * 监听事件
     */
    public function listen(string $event, callable $listener): void;

    /**
     * 订阅事件
     */
    public function subscribe(string $subscriber): void;

    /**
     * 异步分发事件
     */
    public function dispatchAsync(object $event): void;

    /**
     * 批量分发事件
     */
    public function dispatchBatch(array $events): void;

    /**
     * 分发事件直到返回非null值
     */
    public function until(object $event): mixed;

    /**
     * 忘记事件监听器
     */
    public function forget(string $event): void;

    /**
     * 检查是否有监听器
     */
    public function hasListeners(string $event): bool;

    /**
     * 获取事件监听器
     */
    public function getListeners(string $event): array;
}
