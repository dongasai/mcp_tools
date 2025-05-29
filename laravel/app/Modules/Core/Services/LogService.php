<?php

namespace App\Modules\Core\Services;

use App\Modules\Core\Contracts\LogInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class LogService implements LogInterface
{
    protected array $context = [];
    protected string $currentChannel = 'default';
    protected array $channels;

    public function __construct()
    {
        $this->channels = config('core.logging.channels', [
            'performance' => 'daily',
            'audit' => 'database',
            'error' => 'stack',
        ]);
    }

    /**
     * 记录调试信息
     */
    public function debug(string $message, array $context = []): void
    {
        $this->log('debug', $message, $context);
    }

    /**
     * 记录信息
     */
    public function info(string $message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }

    /**
     * 记录警告
     */
    public function warning(string $message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }

    /**
     * 记录错误
     */
    public function error(string $message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }

    /**
     * 记录严重错误
     */
    public function critical(string $message, array $context = []): void
    {
        $this->log('critical', $message, $context);
    }

    /**
     * 记录性能日志
     */
    public function performance(string $operation, float $duration, array $context = []): void
    {
        $performanceContext = array_merge($context, [
            'operation' => $operation,
            'duration' => $duration,
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true),
            'timestamp' => microtime(true),
        ]);

        $this->channel('performance')->info("Performance: {$operation}", $performanceContext);
    }

    /**
     * 记录审计日志
     */
    public function audit(string $action, string $user, array $data = []): void
    {
        $auditContext = [
            'action' => $action,
            'user' => $user,
            'data' => $data,
            'ip' => request()->ip() ?? 'unknown',
            'user_agent' => request()->userAgent() ?? 'unknown',
            'timestamp' => now()->toISOString(),
        ];

        // 审计日志写入数据库
        $this->writeAuditToDatabase($auditContext);
        
        // 同时写入日志文件
        $this->channel('audit')->info("Audit: {$action}", $auditContext);
    }

    /**
     * 记录安全日志
     */
    public function security(string $event, array $context = []): void
    {
        $securityContext = array_merge($context, [
            'event' => $event,
            'ip' => request()->ip() ?? 'unknown',
            'user_agent' => request()->userAgent() ?? 'unknown',
            'timestamp' => now()->toISOString(),
            'severity' => 'security',
        ]);

        $this->channel('error')->warning("Security: {$event}", $securityContext);
    }

    /**
     * 设置日志上下文
     */
    public function withContext(array $context): self
    {
        $instance = clone $this;
        $instance->context = array_merge($this->context, $context);
        return $instance;
    }

    /**
     * 设置日志渠道
     */
    public function channel(string $channel): self
    {
        $instance = clone $this;
        $instance->currentChannel = $channel;
        return $instance;
    }

    /**
     * 通用日志记录方法
     */
    protected function log(string $level, string $message, array $context = []): void
    {
        $fullContext = array_merge($this->context, $context, [
            'module' => 'core',
            'timestamp' => now()->toISOString(),
        ]);

        try {
            if ($this->currentChannel !== 'default' && isset($this->channels[$this->currentChannel])) {
                Log::channel($this->channels[$this->currentChannel])->$level($message, $fullContext);
            } else {
                Log::$level($message, $fullContext);
            }
        } catch (\Exception $e) {
            // 如果日志记录失败，尝试使用默认渠道
            Log::error('Log service error', [
                'original_message' => $message,
                'original_level' => $level,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 将审计日志写入数据库
     */
    protected function writeAuditToDatabase(array $context): void
    {
        try {
            DB::table('audit_logs')->insert([
                'action' => $context['action'],
                'user' => $context['user'],
                'data' => json_encode($context['data']),
                'ip_address' => $context['ip'],
                'user_agent' => $context['user_agent'],
                'created_at' => now(),
            ]);
        } catch (\Exception $e) {
            // 如果数据库写入失败，记录到文件
            Log::error('Audit log database write failed', [
                'context' => $context,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 清理过期日志
     */
    public function cleanup(): void
    {
        $retentionDays = config('core.logging.retention_days', 30);
        $cutoffDate = now()->subDays($retentionDays);

        try {
            DB::table('audit_logs')
                ->where('created_at', '<', $cutoffDate)
                ->delete();

            $this->info('Log cleanup completed', [
                'retention_days' => $retentionDays,
                'cutoff_date' => $cutoffDate->toISOString(),
            ]);
        } catch (\Exception $e) {
            $this->error('Log cleanup failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * 获取日志统计
     */
    public function getStats(int $days = 7): array
    {
        try {
            $startDate = now()->subDays($days);
            
            return [
                'total_logs' => DB::table('audit_logs')
                    ->where('created_at', '>=', $startDate)
                    ->count(),
                'by_action' => DB::table('audit_logs')
                    ->where('created_at', '>=', $startDate)
                    ->select('action', DB::raw('count(*) as count'))
                    ->groupBy('action')
                    ->get()
                    ->pluck('count', 'action')
                    ->toArray(),
                'by_user' => DB::table('audit_logs')
                    ->where('created_at', '>=', $startDate)
                    ->select('user', DB::raw('count(*) as count'))
                    ->groupBy('user')
                    ->orderByDesc('count')
                    ->limit(10)
                    ->get()
                    ->pluck('count', 'user')
                    ->toArray(),
            ];
        } catch (\Exception $e) {
            $this->error('Failed to get log stats', ['error' => $e->getMessage()]);
            return [];
        }
    }
}
