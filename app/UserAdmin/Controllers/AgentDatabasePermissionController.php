<?php

namespace App\UserAdmin\Controllers;

use App\Modules\Dbcont\Models\AgentDatabasePermission;
use App\Modules\Dbcont\Models\DatabaseConnection;
use App\Modules\Dbcont\Enums\PermissionLevel;
use App\Modules\MCP\Models\Agent;
use Dcat\Admin\Http\Controllers\AdminController;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Layout\Content;

class AgentDatabasePermissionController extends AdminController
{
    protected $title = 'Agent数据库权限管理';

    public function index(Content $content)
    {
        return $content
            ->title($this->title)
            ->description('管理您的Agent数据库访问权限')
            ->body($this->grid());
    }

    protected function grid()
    {
        return Grid::make(new AgentDatabasePermission(), function (Grid $grid) {
            // 只显示当前用户的Agent权限
            $user = $this->getCurrentUser();
            if ($user) {
                $grid->model()->whereHas('agent', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                })->with(['agent', 'databaseConnection']);
            }

            $grid->column('id', 'ID')->sortable();
            
            $grid->column('agent.name', 'Agent名称')->limit(20);
            $grid->column('agent.identifier', 'Agent ID')->limit(25);
            
            $grid->column('databaseConnection.name', '数据库连接')->limit(20);
            $grid->column('databaseConnection.driver', '数据库类型')->using([
                'mysql' => 'MySQL',
                'pgsql' => 'PostgreSQL',
                'sqlsrv' => 'SQL Server',
                'sqlite' => 'SQLite',
            ])->label([
                'mysql' => 'primary',
                'pgsql' => 'info',
                'sqlsrv' => 'warning',
                'sqlite' => 'success',
            ]);

            $grid->column('permission_level', '权限级别')->display(function ($value) {
                $labels = [
                    'READ_ONLY' => '只读',
                    'READ_WRITE' => '读写',
                    'ADMIN' => '管理员',
                ];
                $key = $value instanceof \App\Modules\Dbcont\Enums\PermissionLevel ? $value->value : $value;
                return $labels[$key] ?? $key;
            })->label([
                'READ_ONLY' => 'info',
                'READ_WRITE' => 'warning',
                'ADMIN' => 'danger',
            ]);

            $grid->column('allowed_tables', '允许的表')->display(function ($tables) {
                if (empty($tables)) {
                    return '<span class="text-muted">全部表</span>';
                }
                return is_array($tables) ? implode(', ', array_slice($tables, 0, 3)) . (count($tables) > 3 ? '...' : '') : $tables;
            });

            $grid->column('denied_operations', '禁止的操作')->display(function ($operations) {
                if (empty($operations)) {
                    return '<span class="text-muted">无限制</span>';
                }
                return is_array($operations) ? implode(', ', $operations) : $operations;
            });

            $grid->column('max_query_time', '最大查询时间(秒)');
            $grid->column('max_result_rows', '最大结果行数');
            $grid->column('created_at', '创建时间')->sortable();

            // 筛选器
            $grid->filter(function($filter) use ($user) {
                if ($user) {
                    // Agent筛选
                    $agents = Agent::where('user_id', $user->id)->pluck('name', 'id');
                    $filter->equal('agent_id', 'Agent')->select($agents);

                    // 数据库连接筛选
                    $connections = DatabaseConnection::where('user_id', $user->id)->pluck('name', 'id');
                    $filter->equal('database_connection_id', '数据库连接')->select($connections);
                }

                // 权限级别筛选
                $filter->equal('permission_level', '权限级别')->select([
                    'READ_ONLY' => '只读',
                    'READ_WRITE' => '读写',
                    'ADMIN' => '管理员',
                ]);
            });

            // 禁用批量删除
            $grid->disableBatchActions();

            $grid->actions(function (Grid\Displayers\Actions $actions) {
                $actions->disableView();
            });
        });
    }

    protected function form()
    {
        return Form::make(new AgentDatabasePermission(), function (Form $form) {
            $user = $this->getCurrentUser();
            
            $form->display('id', 'ID');

            // Agent选择 - 只显示当前用户的Agent
            $agents = Agent::where('user_id', $user->id)->pluck('name', 'id');
            $form->select('agent_id', 'Agent')
                ->options($agents)
                ->required()
                ->help('选择要授权的Agent');

            // 数据库连接选择 - 只显示当前用户的连接
            $connections = DatabaseConnection::where('user_id', $user->id)->pluck('name', 'id');
            $form->select('database_connection_id', '数据库连接')
                ->options($connections)
                ->required()
                ->help('选择要授权访问的数据库连接');

            $form->select('permission_level', '权限级别')
                ->options([
                    'READ_ONLY' => '只读 - 仅允许SELECT查询',
                    'READ_WRITE' => '读写 - 允许SELECT、INSERT、UPDATE、DELETE',
                    'ADMIN' => '管理员 - 允许所有操作包括DDL',
                ])
                ->default('READ_ONLY')
                ->required()
                ->help('设置Agent的数据库访问权限级别');

            $form->tags('allowed_tables', '允许的表')
                ->help('留空表示允许访问所有表，否则只能访问指定的表');

            $form->tags('denied_operations', '禁止的操作')
                ->options([
                    'DROP' => 'DROP - 删除表/数据库',
                    'TRUNCATE' => 'TRUNCATE - 清空表',
                    'ALTER' => 'ALTER - 修改表结构',
                    'CREATE' => 'CREATE - 创建表/数据库',
                    'DELETE' => 'DELETE - 删除数据',
                ])
                ->help('选择要禁止的特定操作');

            $form->number('max_query_time', '最大查询时间(秒)')
                ->default(30)
                ->min(1)
                ->max(300)
                ->help('单个查询的最大执行时间，超时将被终止');

            $form->number('max_result_rows', '最大结果行数')
                ->default(1000)
                ->min(1)
                ->max(10000)
                ->help('单个查询返回的最大行数，超出将被截断');

            $form->display('created_at', '创建时间');
            $form->display('updated_at', '更新时间');

            // 验证唯一性
            $form->saving(function (Form $form) {
                $exists = AgentDatabasePermission::where('agent_id', $form->agent_id)
                    ->where('database_connection_id', $form->database_connection_id)
                    ->where('id', '!=', $form->model()->id ?? 0)
                    ->exists();

                if ($exists) {
                    return $form->response()->error('该Agent已经有此数据库连接的权限配置');
                }
            });
        });
    }

    protected function detail($id)
    {
        return Show::make($id, new AgentDatabasePermission(), function (Show $show) {
            $show->field('id', 'ID');
            $show->field('agent.name', 'Agent名称');
            $show->field('agent.identifier', 'Agent ID');
            $show->field('databaseConnection.name', '数据库连接');
            $show->field('databaseConnection.host', '数据库主机');
            $show->field('databaseConnection.database', '数据库名');
            
            $show->field('permission_level', '权限级别')->as(function ($value) {
                $labels = [
                    'READ_ONLY' => '只读',
                    'READ_WRITE' => '读写',
                    'ADMIN' => '管理员',
                ];
                $key = $value instanceof \App\Modules\Dbcont\Enums\PermissionLevel ? $value->value : $value;
                return $labels[$key] ?? $key;
            });

            $show->field('allowed_tables', '允许的表')->as(function ($tables) {
                if (empty($tables)) {
                    return '全部表';
                }
                return is_array($tables) ? implode(', ', $tables) : $tables;
            });

            $show->field('denied_operations', '禁止的操作')->as(function ($operations) {
                if (empty($operations)) {
                    return '无限制';
                }
                return is_array($operations) ? implode(', ', $operations) : $operations;
            });

            $show->field('max_query_time', '最大查询时间(秒)');
            $show->field('max_result_rows', '最大结果行数');
            $show->field('created_at', '创建时间');
            $show->field('updated_at', '更新时间');
        });
    }

    /**
     * 获取当前用户
     */
    protected function getCurrentUser()
    {
        return auth('user-admin')->user();
    }
}
