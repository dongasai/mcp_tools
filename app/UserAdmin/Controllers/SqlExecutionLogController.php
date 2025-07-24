<?php

namespace App\UserAdmin\Controllers;

use App\Modules\Dbcont\Models\SqlExecutionLog;
use App\Modules\Dbcont\Models\DatabaseConnection;
use App\Modules\Agent\Models\Agent;
use Dcat\Admin\Http\Controllers\AdminController;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Layout\Content;
use Illuminate\Http\Request;

class SqlExecutionLogController extends AdminController
{
    protected $title = 'SQL执行日志';

    public function index(Content $content)
    {
        return $content
            ->title($this->title)
            ->description('查看您的Agent SQL执行历史')
            ->body($this->grid());
    }

    protected function grid()
    {
        return Grid::make(new SqlExecutionLog(), function (Grid $grid) {
            // 只显示当前用户的Agent执行日志
            $user = $this->getCurrentUser();
            if ($user) {
                $grid->model()->whereHas('agent', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                })->with(['agent', 'databaseConnection']);
            }

            // 默认按执行时间倒序排列
            $grid->model()->orderBy('executed_at', 'desc');

            $grid->column('id', 'ID')->sortable();
            
            $grid->column('agent.name', 'Agent名称')->limit(15);
            $grid->column('agent.identifier', 'Agent ID')->limit(20);
            
            $grid->column('databaseConnection.name', '数据库连接')->limit(15);
            $grid->column('databaseConnection.database', '数据库名')->limit(15);

            $grid->column('sql_statement', 'SQL语句')
                ->limit(50)
                ->help('点击查看完整SQL')
                ->modal('SQL语句详情', function ($model) {
                    return '<pre style="max-height: 400px; overflow-y: auto;">' . htmlspecialchars($model->sql_statement) . '</pre>';
                });

            $grid->column('execution_time', '执行时间(ms)')
                ->sortable()
                ->label(function ($value) {
                    if ($value < 100) return 'success';
                    if ($value < 1000) return 'warning';
                    return 'danger';
                });

            $grid->column('rows_affected', '影响行数')->sortable();

            $grid->column('status', '执行状态')->using([
                'success' => '成功',
                'error' => '失败',
                'timeout' => '超时',
            ])->label([
                'success' => 'success',
                'error' => 'danger',
                'timeout' => 'warning',
            ]);

            $grid->column('error_message', '错误信息')
                ->limit(30)
                ->display(function ($value) {
                    if (empty($value)) {
                        return '<span class="text-muted">-</span>';
                    }
                    return '<span class="text-danger">' . htmlspecialchars($value) . '</span>';
                });

            $grid->column('executed_at', '执行时间')->sortable();

            // 筛选器
            $grid->filter(function($filter) use ($user) {
                $filter->between('executed_at', '执行时间')->datetime();

                // Agent筛选
                $agents = Agent::where('user_id', $user->id)->pluck('name', 'id');
                $filter->equal('agent_id', 'Agent')->select($agents);

                // 数据库连接筛选
                $connections = DatabaseConnection::where('user_id', $user->id)->pluck('name', 'id');
                $filter->equal('database_connection_id', '数据库连接')->select($connections);

                // 执行状态筛选
                $filter->equal('status', '执行状态')->select([
                    'success' => '成功',
                    'error' => '失败',
                    'timeout' => '超时',
                ]);

                // SQL语句模糊搜索
                $filter->like('sql_statement', 'SQL语句');

                // 执行时间范围
                $filter->between('execution_time', '执行时间(ms)');
            });

            // 禁用新增、编辑、删除操作（日志只读）
            $grid->disableCreateButton();
            $grid->disableActions();
            $grid->disableBatchActions();

            // 工具栏
            $grid->tools(function (Grid\Tools $tools) {
                $tools->append('<div class="btn-group">
                    <button type="button" class="btn btn-sm btn-outline-info" id="export-logs">
                        <i class="feather icon-download"></i> 导出日志
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-warning" id="clear-old-logs">
                        <i class="feather icon-trash-2"></i> 清理旧日志
                    </button>
                </div>');
            });

            // 添加统计信息（暂时禁用）
            // $grid->header(function () use ($user) {
            //     $stats = $this->getExecutionStats($user);
            //     return view('user-admin.sql-logs.stats', compact('stats'));
            // });
        });
    }

    protected function detail($id)
    {
        return Show::make($id, new SqlExecutionLog(), function (Show $show) {
            // 确保只能查看自己Agent的日志
            $user = $this->getCurrentUser();
            $show->model()->whereHas('agent', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            });

            $show->field('id', 'ID');
            $show->field('agent.name', 'Agent名称');
            $show->field('agent.identifier', 'Agent ID');
            $show->field('databaseConnection.name', '数据库连接');
            $show->field('databaseConnection.host', '数据库主机');
            $show->field('databaseConnection.database', '数据库名');

            $show->field('sql_statement', 'SQL语句')->as(function ($value) {
                return '<pre style="background: #f8f9fa; padding: 15px; border-radius: 4px; max-height: 400px; overflow-y: auto;">' . htmlspecialchars($value) . '</pre>';
            });

            $show->field('execution_time', '执行时间(ms)');
            $show->field('rows_affected', '影响行数');
            
            $show->field('status', '执行状态')->using([
                'success' => '成功',
                'error' => '失败',
                'timeout' => '超时',
            ]);

            $show->field('error_message', '错误信息')->as(function ($value) {
                if (empty($value)) {
                    return '<span class="text-muted">无错误</span>';
                }
                return '<pre style="background: #fff5f5; color: #e53e3e; padding: 10px; border-radius: 4px;">' . htmlspecialchars($value) . '</pre>';
            });

            $show->field('result_preview', '结果预览')->as(function ($value) {
                if (empty($value)) {
                    return '<span class="text-muted">无结果数据</span>';
                }
                return '<pre style="background: #f0fff4; padding: 10px; border-radius: 4px; max-height: 300px; overflow-y: auto;">' . htmlspecialchars($value) . '</pre>';
            });

            $show->field('executed_at', '执行时间');
        });
    }

    /**
     * 获取执行统计信息
     */
    protected function getExecutionStats($user)
    {
        $baseQuery = SqlExecutionLog::whereHas('agent', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        });

        return [
            'total_executions' => $baseQuery->count(),
            'success_executions' => $baseQuery->where('status', 'success')->count(),
            'error_executions' => $baseQuery->where('status', 'error')->count(),
            'avg_execution_time' => round($baseQuery->avg('execution_time'), 2),
            'today_executions' => $baseQuery->whereDate('executed_at', today())->count(),
            'this_week_executions' => $baseQuery->whereBetween('executed_at', [
                now()->startOfWeek(),
                now()->endOfWeek()
            ])->count(),
        ];
    }

    /**
     * 导出日志
     */
    public function exportLogs(Request $request)
    {
        $user = $this->getCurrentUser();
        
        // 获取筛选条件
        $query = SqlExecutionLog::whereHas('agent', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->with(['agent', 'databaseConnection']);

        // 应用筛选条件
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('executed_at', [$request->start_date, $request->end_date]);
        }

        if ($request->has('agent_id')) {
            $query->where('agent_id', $request->agent_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $logs = $query->orderBy('executed_at', 'desc')->limit(1000)->get();

        // 生成CSV
        $filename = 'sql_execution_logs_' . date('Y-m-d_H-i-s') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($logs) {
            $file = fopen('php://output', 'w');
            
            // CSV头部
            fputcsv($file, [
                'ID', 'Agent名称', 'Agent ID', '数据库连接', '数据库名',
                'SQL语句', '执行时间(ms)', '影响行数', '执行状态', '错误信息', '执行时间'
            ]);

            // 数据行
            foreach ($logs as $log) {
                fputcsv($file, [
                    $log->id,
                    $log->agent->name ?? '',
                    $log->agent->identifier ?? '',
                    $log->databaseConnection->name ?? '',
                    $log->databaseConnection->database ?? '',
                    $log->sql_statement,
                    $log->execution_time,
                    $log->rows_affected,
                    $log->status,
                    $log->error_message,
                    $log->executed_at,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * 清理旧日志
     */
    public function clearOldLogs(Request $request)
    {
        $user = $this->getCurrentUser();
        $days = $request->get('days', 30); // 默认清理30天前的日志

        $deleted = SqlExecutionLog::whereHas('agent', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->where('executed_at', '<', now()->subDays($days))->delete();

        return response()->json([
            'status' => true,
            'message' => "成功清理了 {$deleted} 条 {$days} 天前的日志记录"
        ]);
    }

    /**
     * 获取当前用户
     */
    protected function getCurrentUser()
    {
        return auth('user-admin')->user();
    }
}
