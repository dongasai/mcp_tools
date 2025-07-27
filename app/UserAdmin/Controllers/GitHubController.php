<?php

namespace App\UserAdmin\Controllers;

use Dcat\Admin\Http\Controllers\AdminController;
use Dcat\Admin\Layout\Content;
use Dcat\Admin\Widgets\Card;
use Dcat\Admin\Widgets\Table;
use Modules\User\Models\User;
// 暂时注释掉不存在的模型
// use App\Modules\GitHub\Models\GitHubConnection;
// use App\Modules\GitHub\Models\GitHubRepository;
use Illuminate\Http\Request;

class GitHubController extends AdminController
{
    protected $title = 'GitHub集成';

    public function index(Content $content)
    {
        $user = $this->getCurrentUser();
        $connection = $this->getUserGitHubConnection($user);

        return $content
            ->title($this->title)
            ->description('管理您的GitHub账户集成')
            ->body($this->buildGitHubDashboard($user, $connection));
    }

    protected function buildGitHubDashboard($user, $connection)
    {
        $cards = [];

        if ($connection) {
            // 已连接GitHub
            $cards[] = $this->connectedAccountCard($connection);
            $cards[] = $this->repositoriesCard($connection);
            $cards[] = $this->recentActivityCard($connection);
        } else {
            // 未连接GitHub
            $cards[] = $this->connectAccountCard();
            $cards[] = $this->benefitsCard();
        }

        // 简化返回，直接显示卡片
        $html = '<div class="row">';
        foreach ($cards as $card) {
            $html .= '<div class="col-md-6 mb-3">' . $card . '</div>';
        }
        $html .= '</div>';

        return $html;
    }

    protected function connectedAccountCard($connection)
    {
        return Card::make('GitHub账户', view('user-admin::github.connected-account', [
            'connection' => $connection,
            'avatar_url' => $connection->avatar_url,
            'username' => $connection->github_username,
            'name' => $connection->github_name,
            'connected_at' => $connection->created_at->diffForHumans(),
            'repositories_count' => $connection->repositories()->count(),
            'last_sync' => $connection->last_sync_at ? $connection->last_sync_at->diffForHumans() : '从未同步'
        ]));
    }

    protected function repositoriesCard($connection)
    {
        $repositories = $connection->repositories()->latest()->limit(10)->get();

        $table = new Table(['仓库名称', '语言', '星标', '最后更新'], $repositories->map(function($repo) {
            return [
                '<a href="' . $repo->html_url . '" target="_blank">' . $repo->name . '</a>',
                $repo->language ?: '未知',
                $repo->stargazers_count,
                $repo->updated_at->diffForHumans()
            ];
        })->toArray());

        return Card::make('我的仓库', $table);
    }

    protected function recentActivityCard($connection)
    {
        // 模拟最近活动数据
        $activities = [
            [
                'type' => 'push',
                'repo' => 'user/example-repo',
                'message' => '推送了 3 个提交',
                'time' => '2小时前'
            ],
            [
                'type' => 'create',
                'repo' => 'user/new-project',
                'message' => '创建了新仓库',
                'time' => '1天前'
            ],
            [
                'type' => 'issue',
                'repo' => 'user/example-repo',
                'message' => '创建了新Issue',
                'time' => '2天前'
            ]
        ];

        return Card::make('最近活动', view('user-admin::github.recent-activity', [
            'activities' => $activities
        ]));
    }

    protected function connectAccountCard()
    {
        $html = '
        <div class="card">
            <div class="card-header">
                <h4>连接GitHub账户</h4>
            </div>
            <div class="card-body">
                <p>连接您的GitHub账户以享受以下功能：</p>
                <ul>
                    <li>自动同步您的仓库信息</li>
                    <li>在项目中直接关联GitHub仓库</li>
                    <li>跟踪代码提交和Issue</li>
                    <li>集成GitHub Actions状态</li>
                </ul>
                <div class="mt-3">
                    <button class="btn btn-primary" onclick="alert(\'GitHub集成功能开发中...\')">
                        <i class="fa fa-github"></i> 连接GitHub账户
                    </button>
                </div>
            </div>
        </div>';

        return Card::make('GitHub集成', $html);
    }

    protected function benefitsCard()
    {
        $html = '
        <div class="row">
            <div class="col-md-4">
                <div class="text-center mb-3">
                    <i class="fa fa-sync fa-2x text-primary"></i>
                    <h5 class="mt-2">自动同步</h5>
                    <p class="text-muted">自动同步您的GitHub仓库和提交记录</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="text-center mb-3">
                    <i class="fa fa-link fa-2x text-success"></i>
                    <h5 class="mt-2">项目关联</h5>
                    <p class="text-muted">将项目与GitHub仓库关联，统一管理</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="text-center mb-3">
                    <i class="fa fa-chart-line fa-2x text-info"></i>
                    <h5 class="mt-2">数据分析</h5>
                    <p class="text-muted">分析代码提交频率和项目活跃度</p>
                </div>
            </div>
        </div>';

        return Card::make('集成优势', $html);
    }

    public function connect(Request $request)
    {
        $user = $this->getCurrentUser();

        if (!$user) {
            return response()->json(['status' => false, 'message' => '用户不存在']);
        }

        // 这里应该实现GitHub OAuth流程
        // 为了演示，我们创建一个模拟的连接
        $connection = GitHubConnection::updateOrCreate(
            ['user_id' => $user->id],
            [
                'github_id' => '12345678',
                'github_username' => 'demo_user',
                'github_name' => 'Demo User',
                'avatar_url' => asset('images/default-avatar.png'),
                'access_token' => 'demo_token_' . time(),
                'token_type' => 'bearer',
                'scope' => 'repo,user',
                'last_sync_at' => now(),
            ]
        );

        // 同步仓库信息（模拟）
        $this->syncRepositories($connection);

        return response()->json([
            'status' => true,
            'message' => 'GitHub账户连接成功！',
            'redirect' => route('user-admin.github.index')
        ]);
    }

    public function disconnect(Request $request)
    {
        $user = $this->getCurrentUser();
        $connection = $this->getUserGitHubConnection($user);

        if ($connection) {
            $connection->delete();
            return response()->json(['status' => true, 'message' => 'GitHub账户已断开连接']);
        }

        return response()->json(['status' => false, 'message' => '未找到GitHub连接']);
    }

    protected function syncRepositories($connection)
    {
        // 模拟同步仓库数据
        $mockRepos = [
            [
                'github_id' => 123456,
                'name' => 'example-project',
                'full_name' => 'demo_user/example-project',
                'description' => '一个示例项目',
                'html_url' => 'https://github.com/demo_user/example-project',
                'language' => 'PHP',
                'stargazers_count' => 15,
                'forks_count' => 3,
                'is_private' => false,
            ],
            [
                'github_id' => 123457,
                'name' => 'my-tools',
                'full_name' => 'demo_user/my-tools',
                'description' => '个人工具集合',
                'html_url' => 'https://github.com/demo_user/my-tools',
                'language' => 'JavaScript',
                'stargazers_count' => 8,
                'forks_count' => 1,
                'is_private' => true,
            ]
        ];

        foreach ($mockRepos as $repoData) {
            GitHubRepository::updateOrCreate(
                [
                    'github_connection_id' => $connection->id,
                    'github_id' => $repoData['github_id']
                ],
                $repoData
            );
        }
    }

    protected function getCurrentUser()
    {
        $userAdminUser = auth('user-admin')->user();
        return User::where('name', $userAdminUser->name)->first();
    }

    protected function getUserGitHubConnection($user)
    {
        if (!$user) return null;
        // 暂时返回null，后续实现GitHub集成
        return null; // GitHubConnection::where('user_id', $user->id)->first();
    }
}
