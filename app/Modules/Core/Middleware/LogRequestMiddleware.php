<?php

namespace App\Modules\Core\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Modules\Core\Contracts\LogInterface;

class LogRequestMiddleware
{
    protected LogInterface $logger;

    public function __construct(LogInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * 处理传入的请求
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);

        // 记录请求开始
        $this->logger->info('Request started', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'user_id' => auth()->id(),
            'request_id' => $request->header('X-Request-ID', uniqid()),
        ]);

        $response = $next($request);

        $duration = microtime(true) - $startTime;
        $memoryUsed = memory_get_usage(true) - $startMemory;

        // 记录请求完成
        $this->logger->performance('Request completed', $duration, [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'status' => $response->getStatusCode(),
            'duration' => $duration,
            'memory_used' => $memoryUsed,
            'peak_memory' => memory_get_peak_usage(true),
            'user_id' => auth()->id(),
        ]);

        // 如果响应时间过长，记录警告
        if ($duration > 5.0) {
            $this->logger->warning('Slow request detected', [
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'duration' => $duration,
                'status' => $response->getStatusCode(),
            ]);
        }

        return $response;
    }
}
