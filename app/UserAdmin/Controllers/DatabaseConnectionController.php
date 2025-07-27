<?php

namespace App\UserAdmin\Controllers;

use Modules\Dbcont\Models\DatabaseConnection;
use Modules\Dbcont\Services\DatabaseConnectionService;
use Dcat\Admin\Http\Controllers\AdminController;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Layout\Content;
use Illuminate\Http\Request;

class DatabaseConnectionController extends AdminController
{
    protected $title = '数据库连接管理';
    protected DatabaseConnectionService $connectionService;

    public function __construct(DatabaseConnectionService $connectionService)
    {
        $this->connectionService = $connectionService;
    }

    public function index(Content $content)
    {
        return $content
            ->title($this->title)
            ->description('管理您的数据库连接')
            ->body($this->grid());
    }

    protected function grid()
    {
        return Grid::make(new DatabaseConnection(), function (Grid $grid) {
            // 只显示当前用户的数据库连接
            $user = $this->getCurrentUser();
            if ($user) {
                $grid->model()->where('user_id', $user->id);
            }

            $grid->column('id', 'ID')->sortable();
            $grid->column('name', '连接名称')->limit(30);
            $grid->column('driver', '数据库类型')->using([
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
            
            $grid->column('host', '主机地址')->limit(20);
            $grid->column('port', '端口');
            $grid->column('database', '数据库名')->limit(20);
            $grid->column('username', '用户名')->limit(15);
            
            $grid->column('status', '连接状态')->using([
                'active' => '正常',
                'inactive' => '未激活',
                'error' => '连接错误',
            ])->label([
                'active' => 'success',
                'inactive' => 'warning',
                'error' => 'danger',
            ]);
            
            $grid->column('last_tested_at', '最后测试时间')->sortable();
            $grid->column('created_at', '创建时间')->sortable();

            // 自定义操作
            $grid->actions(function (Grid\Displayers\Actions $actions) {
                $actions->disableView();
                
                // 添加测试连接按钮
                $actions->append('<a href="javascript:void(0)" class="btn btn-xs btn-outline-info test-connection" data-id="'.$actions->getKey().'">
                    <i class="feather icon-wifi"></i> 测试连接
                </a>');
            });

            // 筛选器
            $grid->filter(function($filter) {
                $filter->like('name', '连接名称');
                $filter->equal('driver', '数据库类型')->select([
                    'mysql' => 'MySQL',
                    'pgsql' => 'PostgreSQL',
                    'sqlsrv' => 'SQL Server',
                    'sqlite' => 'SQLite',
                ]);
                $filter->equal('status', '状态')->select([
                    'active' => '正常',
                    'inactive' => '未激活',
                    'error' => '连接错误',
                ]);
            });

            // 禁用批量删除
            $grid->disableBatchActions();
            
            // 工具栏
            $grid->tools(function (Grid\Tools $tools) {
                $tools->append('<div class="btn-group">
                    <button type="button" class="btn btn-sm btn-outline-primary" id="test-all-connections">
                        <i class="feather icon-wifi"></i> 测试所有连接
                    </button>
                </div>');
            });
        });
    }

    protected function form()
    {
        return Form::make(new DatabaseConnection(), function (Form $form) {
            $form->display('id', 'ID');
            
            $form->text('name', '连接名称')
                ->required()
                ->help('为这个数据库连接起一个易识别的名称');
                
            $form->select('driver', '数据库类型')
                ->options([
                    'mysql' => 'MySQL',
                    'pgsql' => 'PostgreSQL',
                    'sqlsrv' => 'SQL Server',
                    'sqlite' => 'SQLite',
                ])
                ->required()
                ->help('选择数据库类型');
                
            $form->text('host', '主机地址')
                ->required()
                ->default('localhost')
                ->help('数据库服务器地址');
                
            $form->number('port', '端口')
                ->required()
                ->default(3306)
                ->help('数据库服务器端口');
                
            $form->text('database', '数据库名')
                ->required()
                ->help('要连接的数据库名称');
                
            $form->text('username', '用户名')
                ->required()
                ->help('数据库用户名');
                
            $form->password('password', '密码')
                ->required()
                ->help('数据库密码');

            $form->textarea('description', '描述')
                ->help('可选的连接描述信息');

            $form->select('status', '状态')
                ->options([
                    'active' => '正常',
                    'inactive' => '未激活',
                ])
                ->default('inactive')
                ->help('连接状态');

            $form->display('created_at', '创建时间');
            $form->display('updated_at', '更新时间');

            // 保存前设置用户ID
            $form->saving(function (Form $form) {
                $user = $this->getCurrentUser();
                if ($user && !$form->model()->user_id) {
                    $form->user_id = $user->id;
                }
            });

            // 保存后测试连接
            $form->saved(function (Form $form) {
                if ($form->model()->status === 'active') {
                    try {
                        $result = $this->connectionService->testConnection($form->model());
                        if (!$result['success']) {
                            admin_warning('连接测试失败', $result['message']);
                            $form->model()->update(['status' => 'error']);
                        } else {
                            admin_success('连接测试成功');
                            $form->model()->update(['last_tested_at' => now()]);
                        }
                    } catch (\Exception $e) {
                        admin_error('连接测试异常', $e->getMessage());
                        $form->model()->update(['status' => 'error']);
                    }
                }
            });
        });
    }

    /**
     * 测试数据库连接
     */
    public function testConnection(Request $request)
    {
        $id = $request->get('id');
        $user = $this->getCurrentUser();
        
        $connection = DatabaseConnection::where('id', $id)
            ->where('user_id', $user->id)
            ->first();
            
        if (!$connection) {
            return response()->json([
                'status' => false,
                'message' => '数据库连接不存在或无权限访问'
            ]);
        }

        try {
            $result = $this->connectionService->testConnection($connection);
            
            // 更新连接状态和测试时间
            $connection->update([
                'status' => $result['success'] ? 'active' : 'error',
                'last_tested_at' => now(),
            ]);

            return response()->json([
                'status' => $result['success'],
                'message' => $result['message'],
                'data' => $result['data'] ?? null
            ]);
        } catch (\Exception $e) {
            $connection->update([
                'status' => 'error',
                'last_tested_at' => now(),
            ]);

            return response()->json([
                'status' => false,
                'message' => '连接测试异常：' . $e->getMessage()
            ]);
        }
    }

    /**
     * 测试所有连接
     */
    public function testAllConnections(Request $request)
    {
        $user = $this->getCurrentUser();
        $connections = DatabaseConnection::where('user_id', $user->id)->get();
        
        $results = [];
        foreach ($connections as $connection) {
            try {
                $result = $this->connectionService->testConnection($connection);
                $connection->update([
                    'status' => $result['success'] ? 'active' : 'error',
                    'last_tested_at' => now(),
                ]);
                
                $results[] = [
                    'id' => $connection->id,
                    'name' => $connection->name,
                    'success' => $result['success'],
                    'message' => $result['message']
                ];
            } catch (\Exception $e) {
                $connection->update([
                    'status' => 'error',
                    'last_tested_at' => now(),
                ]);
                
                $results[] = [
                    'id' => $connection->id,
                    'name' => $connection->name,
                    'success' => false,
                    'message' => '连接异常：' . $e->getMessage()
                ];
            }
        }

        return response()->json([
            'status' => true,
            'message' => '批量测试完成',
            'data' => $results
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
