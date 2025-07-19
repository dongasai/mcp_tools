# CLAUDE.md

本文件为 Claude Code (claude.ai/code) 提供在此代码库中工作的指导。

## 项目概述

**MCP Tools** 是一个基于 Laravel 的 Model Context Protocol (MCP) 服务器，通过 Server-Sent Events (SSE) 提供实时 MCP 服务。它使 AI 代理能够安全地管理项目、任务和 GitHub 集成，通过标准化的 MCP 协议端点进行交互。

> 始终使用中文和用户沟通

## 核心架构

### 双后台系统
- **超级管理员** (`/admin`): 系统级管理，使用 Dcat Admin
- **用户后台** (`/user-admin`): 用户级管理界面

### 模块化结构
- **核心模块**: 基础服务（日志、验证、事件）
- **MCP模块**: 协议实现和 SSE 端点
- **项目模块**: 项目管理和 GitHub 集成
- **任务模块**: 任务生命周期管理
- **Agent模块**: AI 代理注册和权限管理
- **用户模块**: 用户认证和个人资料管理

### 关键端点
- **MCP API**: `/api/mcp/*` - 标准 MCP 协议端点
- **SSE 事件**: `/mcp/sse/*` - AI 代理实时流
- **管理面板**: `/admin` - 超级管理员界面
- **用户面板**: `/user-admin` - 用户管理界面

## 开发命令

### 设置与安装
```bash
# 安装依赖
composer install
npm install

# 环境设置
cp .env.example .env
php artisan key:generate

# 数据库初始化
php artisan migrate
php artisan db:seed

# 启动开发服务器
php artisan serve --port=34004
npm run dev
```

### MCP 操作
```bash
# 启动 MCP SSE 服务器
php artisan mcp:sse:serve --port=34004

# 注册 AI 代理
php artisan mcp:agent:register --name="Claude" --type="claude-3.5" --user-id=1 --projects="1" --permissions="read,create_task"

# 代理管理
php artisan mcp:agent:list --online
php artisan mcp:agent:show {agent_id}
php artisan mcp:agent:grant-project --agent-id={id} --project-id={id} --permissions="read,create_task"
```

### 测试与质量
```bash
# 运行测试
php artisan test

# 运行特定测试
php artisan test tests/Feature/ApiIntegrationTest.php

# 代码风格
php artisan pint

# 静态分析（如有）
./vendor/bin/phpstan analyse
```

### 数据库操作
```bash
# 重置数据库
php artisan migrate:fresh --seed

# 创建迁移
php artisan make:migration create_{table}_table

# 创建填充器
php artisan make:seeder {Table}Seeder
```

## 关键配置文件

- **MCP 配置**: `config/mcp.php` - MCP 服务器设置
- **管理员配置**: `config/admin.php` - Dcat Admin 配置
- **用户后台**: `config/user-admin.php` - 用户后台设置
- **路由**: 查看 `app/Modules/*/routes/api.php` 获取模块路由

## 开发模式

### 模块结构
每个模块遵循 Laravel 包结构：
```
app/Modules/{模块名}/
├── Controllers/     # HTTP 控制器
├── Models/         # Eloquent 模型
├── Services/       # 业务逻辑
├── Events/         # Laravel 事件
├── Providers/      # 服务提供者
├── config/         # 模块配置
└── routes/         # 模块路由
```

### 权限系统
- **代理权限**: 按项目/资源的细粒度控制
- **基于令牌**: MCP 认证的 Bearer 令牌
- **审计日志**: 所有权限操作都有审计日志

### MCP 资源与工具
- **资源**: `project://`, `task://`, `agent://`, `github://`
- **工具**: `create_project`, `create_task`, `sync_github_issues`
- **通知**: 通过 SSE 的实时更新

## 环境变量

MCP 关键环境变量：
```bash
MCP_SERVER_PORT=34004
GITHUB_TOKEN=your_github_token
MCP_ENABLE_ACCESS_CONTROL=true
MCP_DEFAULT_PERMISSIONS=read
```

## 常见开发任务

### 添加新的 MCP 资源
1. 在 `app/Modules/Mcp/Resources/` 中创建资源类
2. 在 `McpServiceProvider::registerMcpResources()` 中注册
3. 在 `app/Modules/Mcp/routes/api.php` 中添加路由

### 添加新的 MCP 工具
1. 在 `app/Modules/Mcp/Tools/` 中创建工具类
2. 在 `McpServiceProvider::registerMcpTools()` 中注册
3. 在 `ToolController` 中添加控制器方法

### 模块开发
1. 在 `app/Modules/` 中创建模块目录
2. 添加扩展基础提供者的服务提供者
3. 在 `config/app.php` 提供者数组中注册
4. 根据需要创建路由、模型、控制器

## 测试策略

- **单元测试**: `tests/Unit/` - 单个组件测试
- **功能测试**: `tests/Feature/` - API 端点测试
- **集成测试**: 跨模块功能测试
- **MCP 协议测试**: MCP 端点验证

## 调试与监控

- **日志**: `storage/logs/laravel.log`
- **MCP 日志**: 查看代理活动的审计日志
- **SSE 监控**: 使用浏览器开发工具监控 SSE 连接
- **数据库**: SQLite 位于 `database/database.sqlite`

## GitHub 集成设置

1. 创建 GitHub 个人访问令牌
2. 添加到 `.env`: `GITHUB_TOKEN=your_token`
3. 配置 webhook: `GITHUB_WEBHOOK_SECRET=your_secret`
4. 使用测试: `php artisan github:test-connection`