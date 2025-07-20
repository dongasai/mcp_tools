<?php

namespace App\Modules\Core\Contracts;

interface ConfigInterface
{
    /**
     * 获取配置值
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * 设置配置值
     */
    public function set(string $key, mixed $value): void;

    /**
     * 检查配置是否存在
     */
    public function has(string $key): bool;

    /**
     * 获取所有配置
     */
    public function all(): array;

    /**
     * 验证配置
     */
    public function validate(array $rules): bool;

    /**
     * 刷新配置缓存
     */
    public function refresh(): void;

    /**
     * 获取环境配置
     */
    public function env(string $key, mixed $default = null): mixed;

    /**
     * 合并配置数组
     */
    public function merge(array $config): void;
}
