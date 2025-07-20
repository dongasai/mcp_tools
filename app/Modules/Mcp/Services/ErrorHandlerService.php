<?php

namespace App\Modules\Mcp\Services;

use App\Modules\Core\Services\LogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ErrorHandlerService
{
    public function __construct(
        private LogService $logger,
        private SessionService $sessionService
    ) {}

    /**
     * 处理MCP错误并返回标准化响应
     */
    public function handleError(\Throwable $exception, ?string $sessionId = null, array $context = []): JsonResponse
    {
        $errorType = $this->classifyError($exception);
        $errorData = $this->formatError($exception, $errorType);

        // 记录到会话（如果有）
        if ($sessionId) {
            $this->sessionService->logError(
                $sessionId,
                $errorType,
                $exception->getMessage(),
                array_merge($context, ['trace' => $exception->getTraceAsString()])
            );
        }

        // 记录到日志
        $this->logError($exception, $errorType, $context);

        return response()->json($errorData, $this->getHttpStatusCode($errorType));
    }

    /**
     * 分类错误类型
     */
    private function classifyError(\Throwable $exception): string
    {
        return match (true) {
            $exception instanceof ValidationException => 'VALIDATION_ERROR',
            $exception instanceof \InvalidArgumentException => 'INVALID_ARGUMENT',
            $exception instanceof \UnexpectedValueException => 'UNEXPECTED_VALUE',
            $exception instanceof HttpException => 'HTTP_ERROR',
            $exception instanceof \PDOException => 'DATABASE_ERROR',
            $exception instanceof \RuntimeException => 'RUNTIME_ERROR',
            $exception instanceof \LogicException => 'LOGIC_ERROR',
            str_contains($exception->getMessage(), 'Permission denied') => 'PERMISSION_DENIED',
            str_contains($exception->getMessage(), 'Access denied') => 'ACCESS_DENIED',
            str_contains($exception->getMessage(), 'Authentication') => 'AUTHENTICATION_ERROR',
            str_contains($exception->getMessage(), 'Token') => 'TOKEN_ERROR',
            str_contains($exception->getMessage(), 'Agent') => 'AGENT_ERROR',
            str_contains($exception->getMessage(), 'Project') => 'PROJECT_ERROR',
            str_contains($exception->getMessage(), 'Task') => 'TASK_ERROR',
            default => 'INTERNAL_ERROR'
        };
    }

    /**
     * 格式化错误响应
     */
    private function formatError(\Throwable $exception, string $errorType): array
    {
        $baseResponse = [
            'success' => false,
            'error' => $this->getUserFriendlyMessage($exception, $errorType),
            'code' => $errorType,
            'timestamp' => now()->toISOString()
        ];

        // 添加特定错误类型的额外信息
        switch ($errorType) {
            case 'VALIDATION_ERROR':
                if ($exception instanceof ValidationException) {
                    $baseResponse['validation_errors'] = $exception->errors();
                }
                break;

            case 'PERMISSION_DENIED':
            case 'ACCESS_DENIED':
                $baseResponse['required_permissions'] = $this->extractRequiredPermissions($exception);
                break;

            case 'AUTHENTICATION_ERROR':
            case 'TOKEN_ERROR':
                $baseResponse['auth_help'] = [
                    'required_headers' => ['X-Agent-Token', 'X-Agent-ID'],
                    'token_generation' => 'Use: php artisan agent:generate-token {agent_id}'
                ];
                break;

            case 'AGENT_ERROR':
                $baseResponse['agent_help'] = [
                    'check_status' => 'Verify agent is active',
                    'check_permissions' => 'Use: php artisan agent:permissions {agent_id} list'
                ];
                break;
        }

        // 在开发环境添加调试信息
        if (config('app.debug')) {
            $baseResponse['debug'] = [
                'exception_class' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString()
            ];
        }

        return $baseResponse;
    }

    /**
     * 获取用户友好的错误消息
     */
    private function getUserFriendlyMessage(\Throwable $exception, string $errorType): string
    {
        return match ($errorType) {
            'VALIDATION_ERROR' => 'The provided data is invalid. Please check the validation errors.',
            'PERMISSION_DENIED' => 'You do not have permission to perform this action.',
            'ACCESS_DENIED' => 'Access denied to the requested resource.',
            'AUTHENTICATION_ERROR' => 'Authentication failed. Please provide valid credentials.',
            'TOKEN_ERROR' => 'Invalid or expired access token.',
            'AGENT_ERROR' => 'Agent configuration or status error.',
            'PROJECT_ERROR' => 'Project access or configuration error.',
            'TASK_ERROR' => 'Task operation error.',
            'DATABASE_ERROR' => 'A database error occurred. Please try again later.',
            'RUNTIME_ERROR' => 'A runtime error occurred during processing.',
            'LOGIC_ERROR' => 'A logic error was detected in the application.',
            'HTTP_ERROR' => $exception->getMessage(),
            'INVALID_ARGUMENT' => 'Invalid argument provided: ' . $exception->getMessage(),
            'UNEXPECTED_VALUE' => 'Unexpected value encountered: ' . $exception->getMessage(),
            default => 'An internal error occurred. Please try again later.'
        };
    }

    /**
     * 获取HTTP状态码
     */
    private function getHttpStatusCode(string $errorType): int
    {
        return match ($errorType) {
            'VALIDATION_ERROR', 'INVALID_ARGUMENT', 'UNEXPECTED_VALUE' => 400,
            'AUTHENTICATION_ERROR', 'TOKEN_ERROR' => 401,
            'PERMISSION_DENIED', 'ACCESS_DENIED' => 403,
            'AGENT_ERROR', 'PROJECT_ERROR', 'TASK_ERROR' => 404,
            'HTTP_ERROR' => 422,
            'DATABASE_ERROR', 'RUNTIME_ERROR', 'LOGIC_ERROR', 'INTERNAL_ERROR' => 500,
            default => 500
        };
    }

    /**
     * 提取所需权限信息
     */
    private function extractRequiredPermissions(\Throwable $exception): array
    {
        $message = $exception->getMessage();
        $permissions = [];

        if (str_contains($message, 'create_task')) {
            $permissions[] = 'create_task';
        }
        if (str_contains($message, 'update_task')) {
            $permissions[] = 'update_task';
        }
        if (str_contains($message, 'complete_task')) {
            $permissions[] = 'complete_task';
        }
        if (str_contains($message, 'add_comment')) {
            $permissions[] = 'add_comment';
        }
        if (str_contains($message, 'read_task')) {
            $permissions[] = 'read_task';
        }
        if (str_contains($message, 'list_tasks')) {
            $permissions[] = 'list_tasks';
        }

        // 提取项目ID
        if (preg_match('/project (\d+)/', $message, $matches)) {
            $permissions['project_access'] = (int)$matches[1];
        }

        return $permissions;
    }

    /**
     * 记录错误到日志
     */
    private function logError(\Throwable $exception, string $errorType, array $context): void
    {
        $logLevel = match ($errorType) {
            'VALIDATION_ERROR', 'INVALID_ARGUMENT' => 'warning',
            'PERMISSION_DENIED', 'ACCESS_DENIED', 'AUTHENTICATION_ERROR', 'TOKEN_ERROR' => 'warning',
            'AGENT_ERROR', 'PROJECT_ERROR', 'TASK_ERROR' => 'info',
            default => 'error'
        };

        $logData = [
            'error_type' => $errorType,
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'context' => $context
        ];

        match ($logLevel) {
            'warning' => $this->logger->warning('MCP Error: ' . $errorType, $logData),
            'info' => $this->logger->info('MCP Info: ' . $errorType, $logData),
            default => $this->logger->error('MCP Error: ' . $errorType, $logData)
        };
    }

    /**
     * 创建标准化的成功响应
     */
    public function successResponse(string $message, array $data = [], array $meta = []): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => $message,
            'timestamp' => now()->toISOString()
        ];

        if (!empty($data)) {
            $response['data'] = $data;
        }

        if (!empty($meta)) {
            $response['meta'] = $meta;
        }

        return response()->json($response);
    }

    /**
     * 创建标准化的验证错误响应
     */
    public function validationErrorResponse(array $errors, string $message = 'Validation failed'): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => $message,
            'code' => 'VALIDATION_ERROR',
            'validation_errors' => $errors,
            'timestamp' => now()->toISOString()
        ], 400);
    }

    /**
     * 创建标准化的权限错误响应
     */
    public function permissionErrorResponse(string $permission, ?int $projectId = null): JsonResponse
    {
        $message = "Permission denied: {$permission}";
        if ($projectId) {
            $message .= " for project {$projectId}";
        }

        return response()->json([
            'success' => false,
            'error' => $message,
            'code' => 'PERMISSION_DENIED',
            'required_permissions' => [$permission],
            'project_id' => $projectId,
            'help' => 'Use: php artisan agent:permissions {agent_id} grant-action ' . $permission,
            'timestamp' => now()->toISOString()
        ], 403);
    }
}
