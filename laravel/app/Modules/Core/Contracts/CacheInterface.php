<?php

namespace App\Modules\Core\Contracts;

interface CacheInterface
{
    /**
     * 获取缓存值
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * 设置缓存值
     */
    public function put(string $key, mixed $value, int $ttl = null): bool;

    /**
     * 删除缓存
     */
    public function forget(string $key): bool;

    /**
     * 清空缓存
     */
    public function flush(): bool;

    /**
     * 记住缓存值
     */
    public function remember(string $key, int $ttl, callable $callback): mixed;

    /**
     * 生成缓存键
     */
    public function generateKey(string $prefix, array $params): string;

    /**
     * 检查缓存是否存在
     */
    public function exists(string $key): bool;

    /**
     * 批量获取缓存
     */
    public function many(array $keys): array;

    /**
     * 批量设置缓存
     */
    public function putMany(array $values, int $ttl = null): bool;

    /**
     * 增加缓存值
     */
    public function increment(string $key, int $value = 1): int;

    /**
     * 减少缓存值
     */
    public function decrement(string $key, int $value = 1): int;
}
