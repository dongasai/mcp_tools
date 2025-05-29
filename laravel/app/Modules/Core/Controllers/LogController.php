<?php

namespace App\Modules\Core\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use App\Modules\Core\Contracts\LogInterface;

class LogController extends Controller
{
    protected LogInterface $logger;

    public function __construct(LogInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * 获取日志列表
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $page = $request->get('page', 1);
            $limit = min($request->get('limit', 50), 100);
            $level = $request->get('level');
            $module = $request->get('module');
            $startDate = $request->get('start_date');
            $endDate = $request->get('end_date');

            $query = DB::table('audit_logs')
                ->orderBy('created_at', 'desc');

            // 应用筛选条件
            if ($level) {
                $query->where('level', $level);
            }

            if ($module) {
                $query->where('module', $module);
            }

            if ($startDate) {
                $query->where('created_at', '>=', $startDate);
            }

            if ($endDate) {
                $query->where('created_at', '<=', $endDate);
            }

            $total = $query->count();
            $logs = $query->offset(($page - 1) * $limit)
                ->limit($limit)
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'logs' => $logs,
                    'pagination' => [
                        'current_page' => $page,
                        'per_page' => $limit,
                        'total' => $total,
                        'last_page' => ceil($total / $limit),
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to get logs', [
                'error' => $e->getMessage(),
                'user' => auth()->user()?->id,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve logs',
            ], 500);
        }
    }

    /**
     * 获取日志统计
     */
    public function stats(Request $request): JsonResponse
    {
        try {
            $days = min($request->get('days', 7), 30);
            
            $stats = $this->logger->getStats($days);

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to get log stats', [
                'error' => $e->getMessage(),
                'user' => auth()->user()?->id,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve log statistics',
            ], 500);
        }
    }

    /**
     * 清理过期日志
     */
    public function cleanup(Request $request): JsonResponse
    {
        try {
            $this->logger->cleanup();

            $this->logger->audit('logs_cleaned', auth()->user()?->id ?? 'system');

            return response()->json([
                'success' => true,
                'message' => 'Log cleanup completed successfully',
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to cleanup logs', [
                'error' => $e->getMessage(),
                'user' => auth()->user()?->id,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to cleanup logs',
            ], 500);
        }
    }

    /**
     * 获取审计日志
     */
    public function audit(Request $request): JsonResponse
    {
        try {
            $page = $request->get('page', 1);
            $limit = min($request->get('limit', 50), 100);
            $action = $request->get('action');
            $user = $request->get('user');
            $startDate = $request->get('start_date');
            $endDate = $request->get('end_date');

            $query = DB::table('audit_logs')
                ->orderBy('created_at', 'desc');

            // 应用筛选条件
            if ($action) {
                $query->where('action', 'like', "%{$action}%");
            }

            if ($user) {
                $query->where('user', 'like', "%{$user}%");
            }

            if ($startDate) {
                $query->where('created_at', '>=', $startDate);
            }

            if ($endDate) {
                $query->where('created_at', '<=', $endDate);
            }

            $total = $query->count();
            $logs = $query->offset(($page - 1) * $limit)
                ->limit($limit)
                ->get()
                ->map(function ($log) {
                    $log->data = json_decode($log->data, true);
                    return $log;
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'logs' => $logs,
                    'pagination' => [
                        'current_page' => $page,
                        'per_page' => $limit,
                        'total' => $total,
                        'last_page' => ceil($total / $limit),
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to get audit logs', [
                'error' => $e->getMessage(),
                'user' => auth()->user()?->id,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve audit logs',
            ], 500);
        }
    }
}
