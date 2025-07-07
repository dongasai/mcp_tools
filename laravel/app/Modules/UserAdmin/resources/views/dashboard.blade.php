<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MCP Tools 用户后台</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background: #343a40;
        }
        .sidebar .nav-link {
            color: #adb5bd;
        }
        .sidebar .nav-link:hover {
            color: #fff;
        }
        .sidebar .nav-link.active {
            color: #fff;
            background-color: #495057;
        }
        .stats-card {
            border-left: 4px solid #007bff;
        }
        .stats-card.success {
            border-left-color: #28a745;
        }
        .stats-card.warning {
            border-left-color: #ffc107;
        }
        .stats-card.danger {
            border-left-color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- 侧边栏 -->
            <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                <div class="position-sticky pt-3">
                    <div class="text-center mb-4">
                        <h4 class="text-white">MCP Tools</h4>
                        <small class="text-muted">用户后台</small>
                    </div>
                    
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="{{ route('user-admin.dashboard') }}">
                                <i class="fas fa-dashboard me-2"></i>
                                工作台
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('user-admin.projects.index') }}">
                                <i class="fas fa-folder me-2"></i>
                                我的项目
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('user-admin.tasks.index') }}">
                                <i class="fas fa-tasks me-2"></i>
                                我的任务
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('user-admin.agents.index') }}">
                                <i class="fas fa-robot me-2"></i>
                                我的Agent
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('user-admin.profile.index') }}">
                                <i class="fas fa-user me-2"></i>
                                个人设置
                            </a>
                        </li>
                    </ul>
                    
                    <hr class="text-muted">
                    
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="/admin" target="_blank">
                                <i class="fas fa-cog me-2"></i>
                                管理后台
                            </a>
                        </li>
                        <li class="nav-item">
                            <form method="POST" action="{{ route('logout') }}" class="d-inline">
                                @csrf
                                <button type="submit" class="nav-link btn btn-link text-start w-100">
                                    <i class="fas fa-sign-out-alt me-2"></i>
                                    退出登录
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- 主内容区 -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">工作台</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-refresh"></i> 刷新
                            </button>
                        </div>
                    </div>
                </div>

                <!-- 欢迎信息 -->
                <div class="alert alert-info">
                    <h4 class="alert-heading">欢迎回来，{{ $user->name }}！</h4>
                    <p>这是您的个人工作台，您可以在这里管理您的项目、任务和Agent。</p>
                </div>

                <!-- 统计卡片 -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stats-card">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            项目总数
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            {{ $stats['total_projects'] }}
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-folder fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stats-card success">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                            已完成任务
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            {{ $stats['completed_tasks'] }}
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-check fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stats-card warning">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                            待处理任务
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            {{ $stats['pending_tasks'] }}
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-clock fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stats-card">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                            活跃Agent
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            {{ $stats['active_agents'] }}
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-robot fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 最近项目和待处理任务 -->
                <div class="row">
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">最近项目</h5>
                            </div>
                            <div class="card-body">
                                @if($recentProjects->count() > 0)
                                    <div class="list-group list-group-flush">
                                        @foreach($recentProjects as $project)
                                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h6 class="mb-1">{{ $project->name }}</h6>
                                                    <small class="text-muted">{{ $project->description }}</small>
                                                </div>
                                                <span class="badge bg-primary rounded-pill">{{ $project->status }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="text-muted">暂无项目</p>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">待处理任务</h5>
                            </div>
                            <div class="card-body">
                                @if($pendingTasks->count() > 0)
                                    <div class="list-group list-group-flush">
                                        @foreach($pendingTasks as $task)
                                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h6 class="mb-1">{{ $task->title }}</h6>
                                                    <small class="text-muted">优先级: {{ $task->priority }}</small>
                                                </div>
                                                <span class="badge bg-warning rounded-pill">{{ $task->status }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="text-muted">暂无待处理任务</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
