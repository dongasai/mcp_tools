# MCP Tools

> 基于 Model Context Protocol (MCP) + SSE 的开发者工具集合，使用PHP Laravel框架实现实时MCP服务器

[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![Laravel](https://img.shields.io/badge/Laravel-11.x-red.svg)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2+-blue.svg)](https://php.net)
[![MCP](https://img.shields.io/badge/MCP-1.0-green.svg)](https://modelcontextprotocol.io)
[![SSE](https://img.shields.io/badge/SSE-Enabled-blue.svg)](https://developer.mozilla.org/en-US/docs/Web/API/Server-sent_events)

## 项目简介

MCP Tools 是一个基于 Model Context Protocol (MCP) 标准的开发者工具集合，通过 **Server-Sent Events (SSE)** 提供实时MCP服务。该项目为AI模型提供标准化的上下文访问接口，支持多Agent并发访问、细粒度权限控制、项目管理、任务协调、代码仓库集成等功能，让AI Agent能够通过标准MCP协议安全高效地完成开发工作。

## 核心特性

### 🔄 MCP + SSE 实时架构
- **实时双向通信**：通过SSE实现MCP协议的实时数据推送
- **多Agent并发**：支持多个AI Agent同时连接和协作
- **连接管理**：自动处理连接断开、重连和心跳检测

### 🔐 Agent权限控制系统
- **唯一身份标识**：每个Agent拥有唯一ID和访问令牌
- **项目级权限**：精确控制Agent可访问的项目和资源
- **操作权限**：细粒度控制Agent可执行的操作类型
- **动态权限管理**：支持实时权限更新和撤销

### 🛡️ 安全访问控制
- **基于Token的认证**：安全的Agent身份验证机制
- **权限继承与委派**：灵活的权限管理体系
- **访问审计日志**：完整的操作记录和权限变更日志
- **资源隔离**：确保Agent只能访问授权的项目和数据

## 核心理念

> 以项目为中心，用户为节点，AiAgent 为目标，为AiAgent提供辅助，让AiAgent能够更好的完成工作

### 核心概念

1. **项目（Project）**：可以是一个或多个代码仓库的集合，所有工作围绕项目展开
2. **代码仓库（Repository）**：以HTTPS地址为唯一标识的Git仓库
3. **用户（User）**：真实存在的平台使用者，可以管理多个项目
4. **AI Agent**：运行的AI智能体，一个用户可以拥有多个Agent
5. **任务（Task）**：Agent的工作单元，需要先认领、再解决，完成后提供回复并标记为已解决

## 技术栈

- **MCP协议**：Model Context Protocol 1.0
- **后端框架**：Laravel 11
- **SSE实现**：纯PHP + ReactPHP异步处理
- **管理界面**：Dcat Admin
- **数据库**：SQLite
- **实时通信**：Server-Sent Events (SSE)
- **版本控制集成**：GitHub API
- **零外部依赖**：无需Node.js、Redis等

## MCP功能特性

### 1. MCP Resources (资源)
- ✅ `project://` - 项目资源访问
- ✅ `task://` - 任务资源访问
- ✅ `github://` - GitHub资源访问
- ✅ `user://` - 用户资源访问
- ✅ `agent://` - Agent资源访问
- 🔄 `repository://` - 代码仓库资源

### 2. MCP Tools (工具)
- ✅ 项目管理工具（创建、更新、删除项目）
- ✅ 任务管理工具（创建、认领、完成任务）
- ✅ GitHub集成工具（同步Issues、创建PR）
- ✅ Agent管理工具（注册、状态更新）
- 🔄 代码分析工具
- 🔄 自动化部署工具

### 3. MCP Notifications (通知)
- ✅ 任务状态变更通知
- ✅ 新任务分配通知
- ✅ GitHub事件通知
- ✅ Agent状态变更通知
- 🔄 系统警报通知

### 4. 项目管理
- ✅ 多仓库项目支持
- ✅ 项目时区设置
- ✅ 项目成员管理
- ✅ 任务生命周期管理
- 🔄 项目模板系统
- 🔄 项目统计分析

### 5. GitHub集成
- ✅ Issues双向同步
- ✅ 自动任务创建
- ✅ 状态同步
- ✅ Webhook支持
- 🔄 Pull Request管理
- 🔄 代码审查集成

### 6. AI Agent支持
- ✅ 标准MCP协议接入
- ✅ 多Agent并发支持
- ✅ 任务智能分配
- ✅ 实时状态监控
- 🔄 Agent性能分析
- 🔄 智能任务推荐

## 快速开始

### 环境要求

- PHP >= 8.2
- Composer
- SQLite

### 安装步骤

1. **克隆项目**
```bash
git clone https://github.com/your-username/mcp_tools.git
cd mcp_tools
```

2. **安装依赖**
```bash
composer install
```

3. **环境配置**
```bash
cp .env.example .env
php artisan key:generate
```

4. **数据库初始化**
```bash
php artisan migrate
php artisan db:seed
```

5. **启动MCP+SSE服务器**
```bash
# 启动Laravel应用（包含MCP SSE服务）
php artisan serve

# 或者启动专用的MCP SSE服务器
php artisan mcp:sse:serve --port=34004

# 监控SSE连接状态（可选）
php artisan mcp:sse:monitor
```

6. **注册AI Agent**
```bash
# 注册第一个Agent
php artisan mcp:agent:register \
  --name="Claude开发助手" \
  --type="claude-3.5-sonnet" \
  --user-id=1 \
  --projects="1" \
  --permissions="read,create_task,update_task,claim_task"

# 输出示例：
# Agent registered successfully!
# Agent ID: agent_001_claude_dev
# Access Token: mcp_token_abc123def456...
# 请保存此Token用于Agent连接
```

7. **访问应用**
- MCP SSE服务器：http://localhost:34004/mcp/sse/connect
- Web管理界面：http://localhost:34004
- 管理后台：http://localhost:34004/admin
- Agent管理：http://localhost:34004/admin/agents

### 配置MCP客户端

#### Claude Desktop配置（SSE模式）
在Claude Desktop的配置文件中添加：
```json
{
  "mcpServers": {
    "mcp-tools": {
      "url": "http://localhost:34004/mcp/sse/connect",
      "headers": {
        "Authorization": "Bearer mcp_token_abc123def456...",
        "Agent-ID": "agent_001_claude_dev",
        "Content-Type": "application/json"
      },
      "transport": "sse"
    }
  }
}
```

#### 其他MCP客户端配置
```json
{
  "mcpServers": {
    "mcp-tools": {
      "url": "http://localhost:34004/mcp/sse/connect?agent_id=agent_001_claude_dev",
      "auth": {
        "type": "bearer",
        "token": "mcp_token_abc123def456..."
      },
      "protocol": "mcp-sse/1.0"
    }
  }
}
```

#### Agent权限管理
```bash
# 查看Agent权限
php artisan mcp:agent:show agent_001_claude_dev

# 为Agent添加项目权限
php artisan mcp:agent:grant-project \
  --agent-id="agent_001_claude_dev" \
  --project-id=2 \
  --permissions="read,create_task"

# 撤销Agent权限
php artisan mcp:agent:revoke-project \
  --agent-id="agent_001_claude_dev" \
  --project-id=2

# 更新Agent操作权限
php artisan mcp:agent:permissions \
  --agent-id="agent_001_claude_dev" \
  --add-permissions="delete_task,manage_github"

# 查看所有在线Agent
php artisan mcp:agent:list --online
```

#### 直接URL连接测试
```bash
# 方式一：Headers认证
curl -N -H "Authorization: Bearer mcp_token_abc123def456..." \
     -H "Agent-ID: agent_001_claude_dev" \
     -H "Accept: text/event-stream" \
     http://localhost:34004/mcp/sse/connect

# 方式二：URL参数认证
curl -N -H "Accept: text/event-stream" \
     "http://localhost:34004/mcp/sse/connect?agent_id=agent_001_claude_dev&token=mcp_token_abc123def456..."

# 方式三：混合认证（推荐）
curl -N -H "Authorization: Bearer mcp_token_abc123def456..." \
     -H "Accept: text/event-stream" \
     "http://localhost:34004/mcp/sse/connect?agent_id=agent_001_claude_dev"

# 预期输出示例：
# data: {"type":"connection_established","agent_id":"agent_001_claude_dev","permissions":{"projects":[1,3,5]}}
# data: {"type":"server_capabilities","capabilities":{"resources":["project://","task://"],"tools":["create_task","claim_task"]}}
# data: {"type":"heartbeat","timestamp":"2024-01-01T12:00:00Z"}
```

### 配置GitHub集成

1. 在GitHub创建Personal Access Token
2. 在`.env`文件中配置：
```env
# GitHub集成
GITHUB_TOKEN=your_github_token
GITHUB_WEBHOOK_SECRET=your_webhook_secret

# MCP+SSE服务器配置
MCP_SERVER_HOST=localhost
MCP_SERVER_PORT=8000
MCP_PROTOCOL_VERSION=1.0
MCP_TRANSPORT=sse

# SSE配置
SSE_ENDPOINT=/mcp/sse/connect
SSE_HEARTBEAT_INTERVAL=30
SSE_CONNECTION_TIMEOUT=300

# Agent权限控制
MCP_ENABLE_ACCESS_CONTROL=true
MCP_DEFAULT_PERMISSIONS=read
MCP_TOKEN_EXPIRY=86400
MCP_MAX_AGENTS_PER_USER=10

# 权限审计
ENABLE_PERMISSION_AUDIT=true
AUDIT_LOG_RETENTION_DAYS=90
```

3. 为Agent配置GitHub访问权限：
```bash
# 为Agent添加GitHub操作权限
php artisan mcp:agent:permissions \
  --agent-id="agent_001_claude_dev" \
  --add-permissions="sync_github_issues,create_github_issue,update_github_issue"

# 限制Agent只能访问特定仓库
php artisan mcp:agent:github-repos \
  --agent-id="agent_001_claude_dev" \
  --add-repos="owner/repo1,owner/repo2"
```

## MCP协议文档

### 连接建立
MCP服务器支持JSON-RPC 2.0协议，通过stdio或TCP进行通信：

```json
{
  "jsonrpc": "2.0",
  "method": "initialize",
  "params": {
    "protocolVersion": "1.0",
    "capabilities": {
      "resources": {},
      "tools": {},
      "notifications": {}
    },
    "clientInfo": {
      "name": "mcp-tools-server",
      "version": "1.0.0"
    }
  }
}
```

### MCP Resources (资源访问)

#### 项目资源
- `project://list` - 获取项目列表
- `project://{id}` - 获取项目详情
- `project://{id}/tasks` - 获取项目任务
- `project://{id}/repositories` - 获取项目仓库

#### 任务资源
- `task://list` - 获取任务列表
- `task://{id}` - 获取任务详情
- `task://assigned/{agent_id}` - 获取Agent任务
- `task://status/{status}` - 按状态筛选任务

#### GitHub资源
- `github://repository/{owner}/{repo}` - 仓库信息
- `github://issues/{owner}/{repo}` - Issues列表
- `github://issue/{owner}/{repo}/{number}` - 特定Issue

### MCP Tools (工具调用)

#### 项目管理工具
- `create_project` - 创建新项目
- `update_project` - 更新项目信息
- `delete_project` - 删除项目

#### 任务管理工具
- `create_task` - 创建新任务
- `claim_task` - 认领任务
- `complete_task` - 完成任务
- `update_task_status` - 更新任务状态

#### GitHub集成工具
- `sync_github_issues` - 同步GitHub Issues
- `create_github_issue` - 创建GitHub Issue
- `update_github_issue` - 更新GitHub Issue

### MCP Notifications (通知)
- `task_status_changed` - 任务状态变更
- `new_task_assigned` - 新任务分配
- `github_issue_updated` - GitHub Issue更新
- `agent_status_changed` - Agent状态变更

## 开发指南

### 项目结构
```
mcp_tools/
├── app/
│   ├── Http/Controllers/    # 控制器
│   │   └── Mcp/            # MCP相关控制器
│   ├── Models/             # 数据模型
│   ├── Services/           # 业务逻辑服务
│   │   └── Mcp/            # MCP服务层
│   ├── Events/             # 事件定义
│   └── Console/Commands/   # Artisan命令
│       └── Mcp/            # MCP相关命令
├── database/
│   ├── migrations/         # 数据库迁移
│   └── seeders/           # 数据填充
├── resources/
│   └── views/             # 视图模板
├── routes/
│   ├── api.php            # API路由
│   ├── web.php            # Web路由
│   └── mcp.php            # MCP路由
└── config/
    └── mcp.php            # MCP配置文件
```

### 开发规范

1. **模块化开发**：按功能模块组织代码
2. **MCP协议标准**：严格遵循MCP协议规范
3. **事件驱动**：使用Laravel事件系统
4. **SSE实时通信**：基于PHP原生SSE实现
5. **权限控制**：细粒度的Agent权限管理
6. **测试驱动**：编写单元测试和功能测试

### 贡献指南

1. Fork项目
2. 创建功能分支：`git checkout -b feature/new-feature`
3. 提交更改：`git commit -am 'Add new feature'`
4. 推送分支：`git push origin feature/new-feature`
5. 创建Pull Request

## 部署

### 生产环境部署

1. **服务器要求**
   - Linux服务器
   - PHP 8.2+ with extensions (curl, json, mbstring, sqlite3)
   - Nginx/Apache
   - SQLite或MySQL

2. **部署步骤**
```bash
# 克隆代码
git clone https://github.com/your-username/mcp_tools.git
cd mcp_tools

# 安装PHP依赖
composer install --optimize-autoloader --no-dev

# 配置环境
cp .env.example .env
# 编辑.env文件设置生产环境配置

# 生成密钥
php artisan key:generate

# 数据库迁移
php artisan migrate --force

# 优化缓存
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 设置权限
chmod -R 755 storage bootstrap/cache

# 启动MCP SSE服务器
php artisan mcp:sse:serve --port=34004 --daemon
```

### Docker部署

```dockerfile
# Dockerfile
FROM php:8.2-fpm

# 安装必要的扩展
RUN apt-get update && apt-get install -y \
    sqlite3 \
    libsqlite3-dev \
    && docker-php-ext-install pdo_sqlite

# 复制应用代码
COPY . /var/www/html
WORKDIR /var/www/html

# 安装Composer依赖
RUN composer install --optimize-autoloader --no-dev

# 设置权限
RUN chmod -R 755 storage bootstrap/cache

# 暴露端口
EXPOSE 34004

# 启动命令
CMD ["php", "artisan", "mcp:sse:serve", "--host=0.0.0.0", "--port=34004"]
```

```bash
# 构建镜像
docker build -t mcp-tools .

# 运行容器
docker run -d -p 34004:34004 --name mcp-tools mcp-tools
```

## 监控与日志

- **应用日志**：`storage/logs/laravel.log`
- **错误监控**：集成Sentry（可选）
- **性能监控**：Laravel Telescope（开发环境）

## 常见问题

### Q: 如何重置数据库？
```bash
php artisan migrate:fresh --seed
```

### Q: 如何清除缓存？
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Q: GitHub集成失败怎么办？
1. 检查GitHub Token权限
2. 验证Webhook配置
3. 查看日志文件排查错误

## 许可证

本项目采用 [MIT许可证](LICENSE)。

## 使用示例

### 基本MCP操作（带权限控制）

#### 1. 查看可访问的项目
```
请显示我可以访问的所有项目列表
```
*系统会根据Agent权限自动过滤项目列表*

#### 2. 创建项目（需要管理员权限）
```
请帮我创建一个新项目，名称为"AI助手开发"，描述为"开发一个智能客服AI助手"
```
*注意：只有具有`create_project`权限的Agent才能执行此操作*

#### 3. 管理任务（权限验证）
```
请查看项目ID为1的所有待处理任务，并帮我认领优先级最高的任务
```
*只能访问Agent有权限的项目中的任务*

#### 4. GitHub集成（项目权限验证）
```
请同步GitHub仓库 owner/repo 的所有Issues到项目1中，并创建对应的任务
```
*需要验证Agent对项目1的访问权限和GitHub操作权限*

### 权限管理功能

#### 查看当前权限
```
请显示我当前的权限和可访问的项目列表
```

#### 申请项目访问权限
```
我需要访问项目ID为5的权限，请帮我申请
```

#### 权限被拒绝的处理
```
# 当Agent尝试访问无权限的资源时
请查看项目ID为999的任务列表

# 系统响应示例
错误：访问被拒绝。您没有访问项目999的权限。
您当前可访问的项目：[1, 3, 5]
如需申请权限，请联系管理员。
```

### 高级功能

#### 多Agent协作
```
请将项目1中标记为"urgent"的任务分配给当前在线的Agent
```
*系统会检查其他Agent的权限和可用性*

#### 实时状态监控
```
请显示当前所有在线Agent的工作状态和正在处理的任务
```
*只显示当前Agent有权限查看的信息*

## 详细文档

📖 **[完整MCP功能文档](docs/MCP协议概述.md)** - 详细的MCP协议实现、工具说明、资源定义等

## 联系我们

- 项目主页：https://github.com/your-username/mcp_tools
- 问题反馈：https://github.com/your-username/mcp_tools/issues
- MCP官方文档：https://modelcontextprotocol.io
- 邮箱：your-email@example.com

## 更新日志

### v1.0.0 (2024-01-01)
- 初始版本发布
- 基础MCP协议实现
- 项目管理功能
- 任务管理系统
- GitHub集成
- 实时通知系统

---

**注意**：
- ✅ 已完成功能
- 🔄 开发中功能
- ❌ 计划中功能

**相关资源**：
- [MCP官方规范](https://modelcontextprotocol.io)
- [Laravel文档](https://laravel.com/docs)
- [GitHub API文档](https://docs.github.com/en/rest)