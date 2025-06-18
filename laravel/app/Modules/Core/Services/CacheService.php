<?php

namespace App\Modules\Core\Services;

use App\Modules\Core\Contracts\CacheInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CacheService implements CacheInterface
{
    protected string $prefix;
    protected int $defaultTtl;
    protected bool $tagsEnabled;

    public function __construct()
    {
        $this->prefix = config('core.cache.prefix', 'mcp_core_');
        $this->defaultTtl = config('core.cache.default_ttl', 3600);
        $this->tagsEnabled = config('core.cache.tags_enabled', true);
    }

    /**
     * 获取缓存值
     */
    public function get(string $key, mixed $default = null): mixed
    {
        try {
            return Cache::get($this->generateKey('', [$key]), $default);
        } catch (\Exception $e) {
            Log::error('缓存获取错误', ['key' => $key, 'error' => $e->getMessage()]);
            return $default;
        }
    }

    /**
     * 设置缓存值
     */
    public function put(string $key, mixed $value, int $ttl = null): bool
    {
        try {
            $ttl = $ttl ?? $this->defaultTtl;
            return Cache::put($this->generateKey('', [$key]), $value, $ttl);
        } catch (\Exception $e) {
            Log::error('缓存设置错误', ['key' => $key, 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * 删除缓存
     */
    public function forget(string $key): bool
    {
        try {
            return Cache::forget($this->generateKey('', [$key]));
        } catch (\Exception $e) {
            Log::error('缓存删除错误', ['key' => $key, 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * 清空缓存
     */
    public function flush(): bool
    {
        try {
            return Cache::flush();
        } catch (\Exception $e) {
            Log::error('缓存清空错误', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * 记住缓存值
     */
    public function remember(string $key, int $ttl, callable $callback): mixed
    {
        try {
            return Cache::remember($this->generateKey('', [$key]), $ttl, $callback);
        } catch (\Exception $e) {
            Log::error('缓存记住错误', ['key' => $key, 'error' => $e->getMessage()]);
            return $callback();
        }
    }

    /**
     * 生成缓存键
     */
    public function generateKey(string $prefix, array $params): string
    {
        $keyPrefix = $this->prefix . ($prefix ? $prefix . ':' : '');
        return $keyPrefix . implode(':', array_map('strval', $params));
    }

    /**
     * 检查缓存是否存在
     */
    public function exists(string $key): bool
    {
        try {
            return Cache::has($this->generateKey('', [$key]));
        } catch (\Exception $e) {
            Log::error('缓存存在检查错误', ['key' => $key, 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * 批量获取缓存
     */
    public function many(array $keys): array
    {
        try {
            $prefixedKeys = array_map(fn($key) => $this->generateKey('', [$key]), $keys);
            $results = Cache::many($prefixedKeys);

            // 移除前缀，恢复原始键名
            $cleanResults = [];
            foreach ($results as $prefixedKey => $value) {
                $originalKey = str_replace($this->prefix, '', $prefixedKey);
                $cleanResults[$originalKey] = $value;
            }

            return $cleanResults;
        } catch (\Exception $e) {
            Log::error('缓存批量获取错误', ['keys' => $keys, 'error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * 批量设置缓存
     */
    public function putMany(array $values, int $ttl = null): bool
    {
        try {
            $ttl = $ttl ?? $this->defaultTtl;
            $prefixedValues = [];

            foreach ($values as $key => $value) {
                $prefixedValues[$this->generateKey('', [$key])] = $value;
            }

            return Cache::putMany($prefixedValues, $ttl);
        } catch (\Exception $e) {
            Log::error('缓存批量设置错误', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * 增加缓存值
     */
    public function increment(string $key, int $value = 1): int
    {
        try {
            return Cache::increment($this->generateKey('', [$key]), $value);
        } catch (\Exception $e) {
            Log::error('缓存递增错误', ['key' => $key, 'error' => $e->getMessage()]);
            return 0;
        }
    }

    /**
     * 减少缓存值
     */
    public function decrement(string $key, int $value = 1): int
    {
        try {
            return Cache::decrement($this->generateKey('', [$key]), $value);
        } catch (\Exception $e) {
            Log::error('缓存递减错误', ['key' => $key, 'error' => $e->getMessage()]);
            return 0;
        }
    }

    /**
     * 使用标签缓存（如果支持）
     */
    public function tags(array $tags): self
    {
        if ($this->tagsEnabled && method_exists(Cache::store(), 'tags')) {
            Cache::tags($tags);
        }
        
        return $this;
    }

    /**
     * 清除标签缓存
     */
    public function flushTags(array $tags): bool
    {
        try {
            if ($this->tagsEnabled && method_exists(Cache::store(), 'tags')) {
                Cache::tags($tags)->flush();
                return true;
            }
            return false;
        } catch (\Exception $e) {
            Log::error('缓存标签清空错误', ['tags' => $tags, 'error' => $e->getMessage()]);
            return false;
        }
    }
}
