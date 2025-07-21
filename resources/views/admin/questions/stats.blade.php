<div class="row">
    <!-- 总体统计 -->
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>{{ $stats['total'] }}</h3>
                <p>总问题数</p>
            </div>
            <div class="icon">
                <i class="fa fa-question-circle"></i>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>{{ $stats['pending'] }}</h3>
                <p>待回答</p>
            </div>
            <div class="icon">
                <i class="fa fa-clock-o"></i>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>{{ $stats['answered'] }}</h3>
                <p>已回答</p>
            </div>
            <div class="icon">
                <i class="fa fa-check-circle"></i>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-6">
        <div class="small-box bg-secondary">
            <div class="inner">
                <h3>{{ $stats['ignored'] }}</h3>
                <p>已忽略</p>
            </div>
            <div class="icon">
                <i class="fa fa-times-circle"></i>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- 优先级统计 -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">优先级分布</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-6">
                        <div class="description-block border-right">
                            <span class="description-percentage text-danger">
                                <i class="fa fa-exclamation-triangle"></i> {{ $stats['urgent'] }}
                            </span>
                            <h5 class="description-header">紧急</h5>
                            <span class="description-text">URGENT</span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="description-block">
                            <span class="description-percentage text-warning">
                                <i class="fa fa-exclamation"></i> {{ $stats['high'] }}
                            </span>
                            <h5 class="description-header">高优先级</h5>
                            <span class="description-text">HIGH</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 过期问题 -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">过期问题</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-12">
                        <div class="description-block">
                            <span class="description-percentage text-danger">
                                <i class="fa fa-clock-o"></i> {{ $stats['expired'] }}
                            </span>
                            <h5 class="description-header">已过期</h5>
                            <span class="description-text">需要处理的过期问题</span>
                        </div>
                    </div>
                </div>
                @if($stats['expired'] > 0)
                <div class="mt-3">
                    <a href="{{ admin_url('questions?status=PENDING&expires_at_end=' . now()->format('Y-m-d H:i:s')) }}" 
                       class="btn btn-sm btn-danger">
                        <i class="fa fa-eye"></i> 查看过期问题
                    </a>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- 快速操作 -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">快速操作</h3>
            </div>
            <div class="card-body">
                <div class="btn-group" role="group">
                    <a href="{{ admin_url('questions?status=PENDING') }}" class="btn btn-warning">
                        <i class="fa fa-clock-o"></i> 查看待回答问题
                    </a>
                    <a href="{{ admin_url('questions?priority=URGENT') }}" class="btn btn-danger">
                        <i class="fa fa-exclamation-triangle"></i> 查看紧急问题
                    </a>
                    <a href="{{ admin_url('questions?priority=HIGH') }}" class="btn btn-warning">
                        <i class="fa fa-exclamation"></i> 查看高优先级问题
                    </a>
                    <a href="{{ admin_url('questions') }}" class="btn btn-primary">
                        <i class="fa fa-list"></i> 查看所有问题
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.small-box {
    border-radius: 0.25rem;
    box-shadow: 0 0 1px rgba(0,0,0,.125), 0 1px 3px rgba(0,0,0,.2);
    display: block;
    margin-bottom: 20px;
    position: relative;
}

.small-box > .inner {
    padding: 10px;
}

.small-box > .small-box-footer {
    background: rgba(0,0,0,.1);
    color: rgba(255,255,255,.8);
    display: block;
    padding: 3px 0;
    position: relative;
    text-align: center;
    text-decoration: none;
    z-index: 10;
}

.small-box > .icon {
    color: rgba(0,0,0,.15);
    z-index: 0;
}

.small-box > .icon > i {
    font-size: 70px;
    position: absolute;
    right: 15px;
    top: 15px;
    transition: transform .3s linear;
}

.small-box:hover {
    text-decoration: none;
    color: #fff;
}

.small-box:hover > .icon > i {
    transform: scale(1.1);
}

.bg-info {
    background-color: #17a2b8!important;
    color: #fff;
}

.bg-warning {
    background-color: #ffc107!important;
    color: #212529;
}

.bg-success {
    background-color: #28a745!important;
    color: #fff;
}

.bg-secondary {
    background-color: #6c757d!important;
    color: #fff;
}

.description-block {
    margin: 0 0 10px;
}

.description-header {
    font-size: 16px;
    margin: 0 0 5px;
}

.description-text {
    font-size: 13px;
    text-transform: uppercase;
}

.description-percentage {
    color: green;
    font-size: 17px;
    font-weight: 600;
}

.border-right {
    border-right: 1px solid #dee2e6!important;
}
</style>
