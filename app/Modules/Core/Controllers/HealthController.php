<?php

namespace App\Modules\Core\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Modules\Core\Contracts\LogInterface;

class HealthController extends Controller
{
    protected LogInterface $logger;

    public function __construct(LogInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * 基础健康检查
     */
    public function check(): JsonResponse
    {
        $status = 'healthy';
        $checks = [];

        // 数据库连接检查
        try {
            DB::connection()->getPdo();
            $checks['database'] = 'ok';
        } catch (\Exception $e) {
            $checks['database'] = 'error';
            $status = 'unhealthy';
        }

        // 缓存检查
        try {
            Cache::put('health_check', 'ok', 60);
            $checks['cache'] = Cache::get('health_check') === 'ok' ? 'ok' : 'error';
        } catch (\Exception $e) {
            $checks['cache'] = 'error';
            $status = 'unhealthy';
        }

        return response()->json([
            'status' => $status,
            'timestamp' => now()->toISOString(),
            'checks' => $checks,
        ], $status === 'healthy' ? 200 : 503);
    }

    /**
     * 详细健康检查
     */
    public function detailed(): JsonResponse
    {
        $status = 'healthy';
        $checks = [];

        // 数据库检查
        $checks['database'] = $this->checkDatabase();
        if ($checks['database']['status'] !== 'ok') {
            $status = 'unhealthy';
        }

        // 缓存检查
        $checks['cache'] = $this->checkCache();
        if ($checks['cache']['status'] !== 'ok') {
            $status = 'unhealthy';
        }

        // 队列检查
        $checks['queue'] = $this->checkQueue();
        if ($checks['queue']['status'] !== 'ok') {
            $status = 'degraded';
        }

        // 磁盘空间检查
        $checks['disk'] = $this->checkDisk();
        if ($checks['disk']['status'] !== 'ok') {
            $status = 'degraded';
        }

        // 内存使用检查
        $checks['memory'] = $this->checkMemory();

        return response()->json([
            'status' => $status,
            'timestamp' => now()->toISOString(),
            'checks' => $checks,
            'version' => config('app.version', '1.0.0'),
            'environment' => config('app.env'),
        ], $status === 'healthy' ? 200 : 503);
    }

    /**
     * 系统信息
     */
    public function info(): JsonResponse
    {
        return response()->json([
            'application' => [
                'name' => config('app.name'),
                'version' => config('app.version', '1.0.0'),
                'environment' => config('app.env'),
                'debug' => config('app.debug'),
                'timezone' => config('app.timezone'),
            ],
            'php' => [
                'version' => PHP_VERSION,
                'memory_limit' => ini_get('memory_limit'),
                'max_execution_time' => ini_get('max_execution_time'),
            ],
            'laravel' => [
                'version' => app()->version(),
                'locale' => config('app.locale'),
            ],
            'server' => [
                'software' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown',
                'php_sapi' => PHP_SAPI,
            ],
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * 检查数据库连接
     */
    protected function checkDatabase(): array
    {
        try {
            $start = microtime(true);
            DB::connection()->getPdo();
            $duration = microtime(true) - $start;

            return [
                'status' => 'ok',
                'response_time' => round($duration * 1000, 2) . 'ms',
                'connection' => config('database.default'),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * 检查缓存系统
     */
    protected function checkCache(): array
    {
        try {
            $start = microtime(true);
            $key = 'health_check_' . time();
            Cache::put($key, 'test', 60);
            $value = Cache::get($key);
            Cache::forget($key);
            $duration = microtime(true) - $start;

            return [
                'status' => $value === 'test' ? 'ok' : 'error',
                'response_time' => round($duration * 1000, 2) . 'ms',
                'driver' => config('cache.default'),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * 检查队列系统
     */
    protected function checkQueue(): array
    {
        try {
            // 简单检查队列配置
            $connection = config('queue.default');
            
            return [
                'status' => 'ok',
                'connection' => $connection,
                'driver' => config("queue.connections.{$connection}.driver"),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * 检查磁盘空间
     */
    protected function checkDisk(): array
    {
        try {
            $path = storage_path();
            $freeBytes = disk_free_space($path);
            $totalBytes = disk_total_space($path);
            $usedBytes = $totalBytes - $freeBytes;
            $usagePercent = round(($usedBytes / $totalBytes) * 100, 2);

            $status = $usagePercent > 90 ? 'warning' : 'ok';
            if ($usagePercent > 95) {
                $status = 'error';
            }

            return [
                'status' => $status,
                'usage_percent' => $usagePercent,
                'free_space' => $this->formatBytes($freeBytes),
                'total_space' => $this->formatBytes($totalBytes),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * 检查内存使用
     */
    protected function checkMemory(): array
    {
        $memoryUsage = memory_get_usage(true);
        $peakMemory = memory_get_peak_usage(true);
        $memoryLimit = $this->parseMemoryLimit(ini_get('memory_limit'));

        $usagePercent = $memoryLimit > 0 ? round(($memoryUsage / $memoryLimit) * 100, 2) : 0;

        return [
            'status' => $usagePercent > 80 ? 'warning' : 'ok',
            'usage_percent' => $usagePercent,
            'current_usage' => $this->formatBytes($memoryUsage),
            'peak_usage' => $this->formatBytes($peakMemory),
            'memory_limit' => $this->formatBytes($memoryLimit),
        ];
    }

    /**
     * 格式化字节数
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * 解析内存限制
     */
    protected function parseMemoryLimit(string $limit): int
    {
        if ($limit === '-1') {
            return 0; // 无限制
        }

        $limit = trim($limit);
        $last = strtolower($limit[strlen($limit) - 1]);
        $value = (int) $limit;

        switch ($last) {
            case 'g':
                $value *= 1024;
            case 'm':
                $value *= 1024;
            case 'k':
                $value *= 1024;
        }

        return $value;
    }
}
