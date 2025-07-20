<?php

namespace App\Core\Logging;

use Monolog\Logger;
use Monolog\Formatter\LineFormatter;
use Monolog\Level;

/**
 * 支持文件大小限制的每日轮转日志驱动
 */
class SizeRotatingDailyLogger
{
    /**
     * 创建自定义 Monolog 实例
     *
     * @param array $config
     * @return Logger
     */
    public function __invoke(array $config): Logger
    {
        $logger = new Logger($config['name'] ?? 'laravel');

        // 获取配置参数
        $path = $config['path'] ?? storage_path('logs/laravel.log');
        $level = $config['level'] ?? 'debug';
        $maxFiles = $config['days'] ?? 7;
        $maxFileSize = $this->parseFileSize($config['max_file_size'] ?? '100M');
        $permission = $config['permission'] ?? null;
        $locking = $config['locking'] ?? false;

        // 创建处理器
        $handler = new SizeRotatingDailyHandler(
            $path,
            $maxFiles,
            $this->parseLevel($level),
            true,
            $permission,
            $locking,
            $maxFileSize
        );

        // 设置格式化器
        $formatter = new LineFormatter(
            $config['format'] ?? null,
            $config['date_format'] ?? null,
            $config['allow_inline_line_breaks'] ?? false,
            $config['ignore_empty_context_and_extra'] ?? false
        );
        
        $handler->setFormatter($formatter);
        $logger->pushHandler($handler);

        return $logger;
    }

    /**
     * 解析日志级别
     *
     * @param string $level
     * @return int
     */
    protected function parseLevel(string $level): int
    {
        return match (strtolower($level)) {
            'debug' => Level::DEBUG->value(),
            'info' => Level::INFO->value(),
            'notice' => Level::NOTICE->value(),
            'warning' => Level::WARNING->value(),
            'error' => Level::ERROR->value(),
            'critical' => Level::CRITICAL->value(),
            'alert' => Level::ALERT->value(),
            'emergency' => Level::EMERGENCY->value(),
            default => Level::DEBUG->value(),
        };
    }

    /**
     * 解析文件大小配置
     *
     * @param string $size
     * @return int
     */
    protected function parseFileSize(string $size): int
    {
        $size = trim($size);
        $unit = strtoupper(substr($size, -1));
        $value = (int) substr($size, 0, -1);

        return match ($unit) {
            'K' => $value * 1024,
            'M' => $value * 1024 * 1024,
            'G' => $value * 1024 * 1024 * 1024,
            default => (int) $size, // 如果没有单位，假设是字节
        };
    }
}
