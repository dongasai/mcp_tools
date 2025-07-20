<?php

namespace App\Modules\Core\Services;

use App\Modules\Core\Contracts\ConfigInterface;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cache;

class ConfigService implements ConfigInterface
{
    protected array $cache = [];
    protected string $cachePrefix = 'config:';
    protected int $cacheTtl = 3600;

    public function __construct()
    {
        $this->cacheTtl = config('core.cache.default_ttl', 3600);
        $this->cachePrefix = config('core.cache.prefix', 'mcp_core_') . 'config:';
    }

    /**
     * 获取配置值
     */
    public function get(string $key, mixed $default = null): mixed
    {
        // 先从内存缓存获取
        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }

        // 从Laravel配置获取
        $value = Config::get($key, $default);
        
        // 缓存到内存
        $this->cache[$key] = $value;
        
        return $value;
    }

    /**
     * 设置配置值
     */
    public function set(string $key, mixed $value): void
    {
        Config::set($key, $value);
        $this->cache[$key] = $value;
        
        // 清除相关缓存
        Cache::forget($this->cachePrefix . $key);
    }

    /**
     * 检查配置是否存在
     */
    public function has(string $key): bool
    {
        return Config::has($key);
    }

    /**
     * 获取所有配置
     */
    public function all(): array
    {
        return Config::all();
    }

    /**
     * 验证配置
     */
    public function validate(array $rules): bool
    {
        foreach ($rules as $key => $rule) {
            $value = $this->get($key);
            
            if (!$this->validateRule($value, $rule)) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * 刷新配置缓存
     */
    public function refresh(): void
    {
        $this->cache = [];
        Cache::flush();
    }

    /**
     * 获取环境配置
     */
    public function env(string $key, mixed $default = null): mixed
    {
        return env($key, $default);
    }

    /**
     * 合并配置数组
     */
    public function merge(array $config): void
    {
        foreach ($config as $key => $value) {
            $this->set($key, $value);
        }
    }

    /**
     * 验证单个规则
     */
    protected function validateRule(mixed $value, string $rule): bool
    {
        switch ($rule) {
            case 'required':
                return !empty($value);
            case 'string':
                return is_string($value);
            case 'integer':
                return is_int($value);
            case 'boolean':
                return is_bool($value);
            case 'array':
                return is_array($value);
            default:
                return true;
        }
    }
}
