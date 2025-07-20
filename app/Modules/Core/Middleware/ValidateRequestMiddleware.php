<?php

namespace App\Modules\Core\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Modules\Core\Contracts\ValidationInterface;
use App\Modules\Core\Contracts\LogInterface;

class ValidateRequestMiddleware
{
    protected ValidationInterface $validator;
    protected LogInterface $logger;

    public function __construct(ValidationInterface $validator, LogInterface $logger)
    {
        $this->validator = $validator;
        $this->logger = $logger;
    }

    /**
     * 处理传入的请求
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 验证请求基本格式
        if (!$this->validateBasicRequest($request)) {
            $this->logger->security('Invalid request format detected', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'url' => $request->fullUrl(),
                'method' => $request->method(),
            ]);

            return response()->json([
                'error' => '无效的请求格式',
                'code' => 'INVALID_REQUEST_FORMAT'
            ], 400);
        }

        // 验证内容类型
        if (!$this->validateContentType($request)) {
            return response()->json([
                'error' => '不支持的内容类型',
                'code' => 'UNSUPPORTED_CONTENT_TYPE'
            ], 415);
        }

        // 验证请求大小
        if (!$this->validateRequestSize($request)) {
            return response()->json([
                'error' => '请求过大',
                'code' => 'REQUEST_TOO_LARGE'
            ], 413);
        }

        return $next($request);
    }

    /**
     * 验证基本请求格式
     */
    protected function validateBasicRequest(Request $request): bool
    {
        // 检查必要的头部
        $requiredHeaders = ['User-Agent'];
        
        foreach ($requiredHeaders as $header) {
            if (!$request->hasHeader($header)) {
                return false;
            }
        }

        // 检查URL格式
        if (!filter_var($request->fullUrl(), FILTER_VALIDATE_URL)) {
            return false;
        }

        return true;
    }

    /**
     * 验证内容类型
     */
    protected function validateContentType(Request $request): bool
    {
        if (!$request->isMethod('POST') && !$request->isMethod('PUT') && !$request->isMethod('PATCH')) {
            return true;
        }

        $contentType = $request->header('Content-Type');
        $allowedTypes = [
            'application/json',
            'application/x-www-form-urlencoded',
            'multipart/form-data',
        ];

        foreach ($allowedTypes as $type) {
            if (str_starts_with($contentType, $type)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 验证请求大小
     */
    protected function validateRequestSize(Request $request): bool
    {
        $maxSize = config('core.performance.max_request_size', 10 * 1024 * 1024); // 10MB
        $contentLength = $request->header('Content-Length', 0);

        return $contentLength <= $maxSize;
    }
}
