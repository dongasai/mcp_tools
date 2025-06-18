# MCP (Model Context Protocol) 功能文档

## 概述

本项目实现了基于 Model Context Protocol (MCP) 标准的服务器，通过 **Server-Sent Events (SSE)** 提供实时的MCP服务。为AI模型提供标准化的上下文访问接口，支持多Agent并发访问和细粒度权限控制。

## MCP + SSE 架构

```
┌─────────────────┐    SSE/HTTP     ┌─────────────────┐
│   AI Agent A    │ ◄──────────────► │                 │
├─────────────────┤                 │   MCP Server    │
│   AI Agent B    │ ◄──────────────► │  (Laravel+SSE)  │
├─────────────────┤                 │                 │
│   AI Agent C    │ ◄──────────────► │                 │
└─────────────────┘                 └─────────────────┘
                                               │
                                               ▼
                                    ┌─────────────────┐
                                    │   Resources &   │
                                    │   Tools with    │
                                    │ Access Control  │
                                    └─────────────────┘
```

### 纯PHP SSE-based MCP 特性
- 🔄 **纯PHP实时通信**：基于PHP原生SSE实现，无需Node.js
- 🔐 **Agent身份认证**：每个Agent拥有唯一标识符和访问令牌
- 🛡️ **项目级权限控制**：精确控制Agent可访问的项目和资源
- 📊 **并发连接管理**：支持多个Agent同时连接和操作
- 🔔 **实时通知系统**：任务状态变更、新任务分配等实时推送
- ⚡ **高性能**：Laravel + ReactPHP异步处理，支持大量并发连接
- 🚀 **零依赖**：无需Node.js、Redis等外部依赖，纯PHP实现

## 核心功能模块

### 1. 项目管理 (Project Management)

#### MCP Resources
- `project://list` - 获取项目列表
- `project://{id}` - 获取特定项目详情
- `project://{id}/repositories` - 获取项目关联的代码仓库
- `project://{id}/tasks` - 获取项目任务列表

#### MCP Tools
- `create_project` - 创建新项目
- `update_project` - 更新项目信息
- `delete_project` - 删除项目
- `add_repository` - 添加代码仓库到项目
- `remove_repository` - 从项目移除代码仓库

#### 功能特性
- ✅ 项目创建与配置
- ✅ 多仓库项目支持
- ✅ 项目时区设置
- ✅ 项目成员管理
- 🔄 项目模板系统
- 🔄 项目统计分析

### 2. 任务管理 (Task Management)

#### MCP Resources
- `task://list` - 获取任务列表
- `task://{id}` - 获取任务详情
- `task://assigned/{agent_id}` - 获取分配给特定Agent的任务
- `task://status/{status}` - 按状态筛选任务

#### MCP Tools
- `create_task` - 创建新任务
- `claim_task` - 认领任务
- `update_task_status` - 更新任务状态
- `complete_task` - 完成任务
- `add_task_comment` - 添加任务评论

#### 任务状态流转
```
pending → claimed → in_progress → completed
   ↓         ↓           ↓           ↓
cancelled  cancelled  cancelled   reopened
```

#### 功能特性
- ✅ 任务生命周期管理
- ✅ 任务分配与认领
- ✅ 任务状态跟踪
- ✅ 任务评论系统
- 🔄 任务优先级管理
- 🔄 任务依赖关系

### 3. GitHub集成 (GitHub Integration) 🔮 *后期扩展功能*

#### MCP Resources
- `github://repository/{owner}/{repo}` - 获取仓库信息
- `github://issues/{owner}/{repo}` - 获取Issues列表
- `github://issue/{owner}/{repo}/{number}` - 获取特定Issue
- `github://pulls/{owner}/{repo}` - 获取Pull Requests
- `github://commits/{owner}/{repo}` - 获取提交历史

#### MCP Tools
- `sync_github_issues` - 同步GitHub Issues到任务
- `create_github_issue` - 创建GitHub Issue
- `update_github_issue` - 更新GitHub Issue
- `close_github_issue` - 关闭GitHub Issue
- `create_pull_request` - 创建Pull Request

#### 功能特性
- ✅ GitHub仓库连接
- ✅ Issues双向同步
- ✅ 自动任务创建
- ✅ 状态同步
- 🔄 Pull Request管理
- 🔄 代码审查集成
- 🔄 Webhook支持

### 4. Agent权限控制与身份管理 (Agent Access Control & Identity)

#### Agent标识符系统
每个AI Agent都有唯一的身份标识和访问控制：

```json
{
  "agent_id": "agent_001_claude_dev",
  "agent_name": "Claude开发助手",
  "agent_type": "claude-3.5-sonnet",
  "access_token": "mcp_token_abc123...",
  "permissions": {
    "projects": [1, 3, 5],
    "actions": ["read", "create_task", "update_task"],
    "resources": ["project://", "task://", "github://"]
  },
  "created_at": "2024-01-01T00:00:00Z",
  "last_active": "2024-01-01T12:00:00Z"
}
```

#### MCP Resources
- `user://profile` - 获取用户配置
- `user://agents` - 获取用户的Agent列表
- `agent://{id}` - 获取Agent详情（需权限）
- `agent://{id}/tasks` - 获取Agent任务（仅自己）
- `agent://{id}/permissions` - 获取Agent权限信息
- `agent://{id}/projects` - 获取Agent可访问的项目列表

#### MCP Tools
- `register_agent` - 注册新Agent（需管理员权限）
- `update_agent_status` - 更新Agent状态
- `request_project_access` - 申请项目访问权限
- `revoke_agent_access` - 撤销Agent访问权限（管理员）
- `get_agent_permissions` - 获取当前Agent权限

#### 权限控制特性
- ✅ 基于项目的访问控制
- ✅ 细粒度操作权限
- ✅ Agent身份认证与授权
- ✅ 访问令牌管理
- ✅ 权限继承与委派
- 🔄 动态权限调整
- 🔄 权限审计日志

### 5. 实时通信 (Real-time Communication)

#### MCP Notifications
- `task_status_changed` - 任务状态变更通知
- `new_task_assigned` - 新任务分配通知
- `github_issue_updated` - GitHub Issue更新通知
- `agent_status_changed` - Agent状态变更通知

#### 功能特性
- ✅ SSE实时数据推送
- ✅ 任务状态实时更新
- ✅ 系统通知
- ✅ 多用户协作同步
- 🔄 消息队列支持
- 🔄 离线消息处理

## MCP + SSE 协议实现

### 1. SSE连接建立
Agent通过URL直接连接到MCP SSE服务器，支持多种连接方式：

#### 方式一：通过Headers认证
```http
GET /mcp/sse/connect HTTP/1.1
Host: localhost:8000
Authorization: Bearer mcp_token_abc123def456...
Agent-ID: agent_001_claude_dev
Accept: text/event-stream
Cache-Control: no-cache
```

#### 方式二：通过URL参数认证
```http
GET /mcp/sse/connect?agent_id=agent_001_claude_dev&token=mcp_token_abc123def456... HTTP/1.1
Host: localhost:8000
Accept: text/event-stream
Cache-Control: no-cache
```

#### 方式三：混合认证（推荐）
```http
GET /mcp/sse/connect?agent_id=agent_001_claude_dev HTTP/1.1
Host: localhost:8000
Authorization: Bearer mcp_token_abc123def456...
Accept: text/event-stream
Cache-Control: no-cache
```

**服务器响应**：
```http
HTTP/1.1 200 OK
Content-Type: text/event-stream
Cache-Control: no-cache
Connection: keep-alive
Access-Control-Allow-Origin: *
Access-Control-Allow-Headers: Authorization, Agent-ID

data: {"type":"connection_established","agent_id":"agent_001_claude_dev","permissions":{"projects":[1,3,5],"actions":["read","create_task","update_task"]}}

data: {"type":"server_capabilities","capabilities":{"resources":["project://","task://","github://"],"tools":["create_task","claim_task","update_task_status"]}}

data: {"type":"heartbeat","timestamp":"2024-01-01T12:00:00Z"}
```

### 2. Agent身份验证
每个请求都需要包含Agent标识和权限验证：

```json
{
  "jsonrpc": "2.0",
  "method": "initialize",
  "params": {
    "protocolVersion": "1.0",
    "agent_id": "agent_001_claude_dev",
    "access_token": "mcp_token_abc123...",
    "capabilities": {
      "resources": {},
      "tools": {},
      "notifications": {}
    },
    "clientInfo": {
      "name": "Claude开发助手",
      "version": "1.0.0",
      "type": "claude-3.5-sonnet"
    }
  }
}
```

### 3. 权限控制的资源访问
资源访问会根据Agent权限进行过滤：

```json
{
  "jsonrpc": "2.0",
  "method": "resources/read",
  "params": {
    "uri": "project://123",
    "agent_id": "agent_001_claude_dev"
  }
}
```

**权限验证失败响应**：
```json
{
  "jsonrpc": "2.0",
  "error": {
    "code": 1004,
    "message": "Access denied: Agent does not have permission to access project 123",
    "data": {
      "agent_id": "agent_001_claude_dev",
      "requested_resource": "project://123",
      "allowed_projects": [1, 3, 5]
    }
  }
}
```

### 4. 带权限的工具调用
工具调用会验证Agent是否有执行权限：

```json
{
  "jsonrpc": "2.0",
  "method": "tools/call",
  "params": {
    "name": "create_task",
    "agent_id": "agent_001_claude_dev",
    "arguments": {
      "title": "Fix bug in user authentication",
      "description": "Users are unable to login with GitHub OAuth",
      "project_id": 3,
      "priority": "high",
      "assigned_to": "agent_001_claude_dev"
    }
  }
}
```

### 5. 实时通知推送
通过SSE推送实时通知给相关Agent：

```
data: {"type":"task_assigned","data":{"task_id":456,"agent_id":"agent_001_claude_dev","project_id":3,"priority":"high"}}

data: {"type":"task_status_changed","data":{"task_id":123,"old_status":"pending","new_status":"claimed","changed_by":"agent_002_gpt4"}}

data: {"type":"permission_updated","data":{"agent_id":"agent_001_claude_dev","new_projects":[1,3,5,7],"action":"project_access_granted"}}
```

## 配置说明

### MCP+SSE服务器配置
```env
# MCP服务器设置
MCP_SERVER_HOST=localhost
MCP_SERVER_PORT=3000
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

# 功能开关
MCP_ENABLE_PROJECTS=true
MCP_ENABLE_TASKS=true
MCP_ENABLE_GITHUB=true
MCP_ENABLE_NOTIFICATIONS=true

# GitHub集成 (后期扩展功能)
GITHUB_TOKEN=your_github_token
GITHUB_WEBHOOK_SECRET=your_webhook_secret

# 权限审计
ENABLE_PERMISSION_AUDIT=true
AUDIT_LOG_RETENTION_DAYS=90
```

### Agent注册与权限配置
```bash
# 注册新Agent
php artisan mcp:agent:register \
  --name="Claude开发助手" \
  --type="claude-3.5-sonnet" \
  --user-id=1 \
  --projects="1,3,5" \
  --permissions="read,create_task,update_task"

# 更新Agent权限
php artisan mcp:agent:permissions \
  --agent-id="agent_001_claude_dev" \
  --add-projects="7,9" \
  --add-permissions="delete_task"

# 撤销Agent访问
php artisan mcp:agent:revoke \
  --agent-id="agent_001_claude_dev" \
  --reason="Security review"
```

### 客户端URL连接配置

#### JavaScript客户端示例
```javascript
// 方式一：通过Headers认证
const eventSource = new EventSource('http://localhost:34004/mcp/sse/connect', {
  headers: {
    'Authorization': 'Bearer mcp_token_abc123def456...',
    'Agent-ID': 'agent_001_claude_dev'
  }
});

// 方式二：通过URL参数认证
const eventSource = new EventSource(
  'http://localhost:34004/mcp/sse/connect?agent_id=agent_001_claude_dev&token=mcp_token_abc123def456...'
);

// 方式三：混合认证（推荐）
const eventSource = new EventSource(
  'http://localhost:34004/mcp/sse/connect?agent_id=agent_001_claude_dev',
  {
    headers: {
      'Authorization': 'Bearer mcp_token_abc123def456...'
    }
  }
);

eventSource.onopen = function(event) {
  console.log('MCP SSE连接已建立');
};

eventSource.onmessage = function(event) {
  const data = JSON.parse(event.data);
  handleMcpMessage(data);
};

eventSource.onerror = function(event) {
  console.error('MCP SSE连接错误:', event);
};

function handleMcpMessage(data) {
  switch(data.type) {
    case 'connection_established':
      console.log('Agent认证成功:', data.agent_id);
      console.log('可访问项目:', data.permissions.projects);
      break;
    case 'task_assigned':
      console.log('新任务分配:', data.data);
      break;
    case 'permission_updated':
      console.log('权限更新:', data.data);
      break;
    case 'heartbeat':
      // 心跳检测，保持连接活跃
      break;
  }
}
```

#### Python客户端示例
```python
import requests
import json
from sseclient import SSEClient

# 连接MCP SSE服务器
url = 'http://localhost:34004/mcp/sse/connect'
headers = {
    'Authorization': 'Bearer mcp_token_abc123def456...',
    'Agent-ID': 'agent_001_claude_dev',
    'Accept': 'text/event-stream'
}

# 或者使用URL参数
# url = 'http://localhost:34004/mcp/sse/connect?agent_id=agent_001_claude_dev&token=mcp_token_abc123def456...'

messages = SSEClient(url, headers=headers)

for msg in messages:
    if msg.data:
        data = json.loads(msg.data)
        print(f"收到MCP消息: {data}")

        if data['type'] == 'connection_established':
            print(f"Agent {data['agent_id']} 连接成功")
            print(f"可访问项目: {data['permissions']['projects']}")
```

#### cURL测试连接
```bash
# 测试SSE连接（Headers认证）
curl -N -H "Authorization: Bearer mcp_token_abc123def456..." \
     -H "Agent-ID: agent_001_claude_dev" \
     -H "Accept: text/event-stream" \
     http://localhost:8000/mcp/sse/connect

# 测试SSE连接（URL参数认证）
curl -N -H "Accept: text/event-stream" \
     "http://localhost:8000/mcp/sse/connect?agent_id=agent_001_claude_dev&token=mcp_token_abc123def456..."

# 测试SSE连接（混合认证）
curl -N -H "Authorization: Bearer mcp_token_abc123def456..." \
     -H "Accept: text/event-stream" \
     "http://localhost:8000/mcp/sse/connect?agent_id=agent_001_claude_dev"
```

### Claude Desktop配置（直接SSE连接）
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

### 其他MCP客户端配置
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

### 纯PHP SSE服务器配置
```env
# 无需Node.js，纯PHP实现
MCP_TRANSPORT=sse
MCP_SSE_PURE_PHP=true
MCP_SSE_ASYNC=true
```

## 使用示例

### 1. 启动纯PHP MCP+SSE服务器
```bash
# 启动Laravel应用（包含MCP SSE服务）
php artisan serve

# 或者启动专用的MCP SSE服务器
php artisan mcp:sse:serve --port=34004

# 后台运行MCP SSE服务器
php artisan mcp:sse:serve --port=34004 --daemon

# 查看当前连接的Agent
php artisan mcp:agent:list --online

# 监控SSE连接状态
php artisan mcp:sse:monitor

# 查看SSE服务器状态
php artisan mcp:sse:status
```

### 2. Agent注册与权限设置
```bash
# 注册Claude Agent
php artisan mcp:agent:register \
  --name="Claude开发助手" \
  --type="claude-3.5-sonnet" \
  --user-id=1 \
  --projects="1,3,5" \
  --permissions="read,create_task,update_task,claim_task"

# 输出: Agent registered successfully
# Agent ID: agent_001_claude_dev
# Access Token: mcp_token_abc123def456...

# 为Agent添加新项目权限
php artisan mcp:agent:grant-project \
  --agent-id="agent_001_claude_dev" \
  --project-id=7 \
  --permissions="read,create_task"
```

### 3. URL连接方式总结

#### 支持的连接URL格式

1. **基础URL**：`http://localhost:8000/mcp/sse/connect`

2. **Headers认证**：
   ```
   URL: http://localhost:8000/mcp/sse/connect
   Headers:
     - Authorization: Bearer {access_token}
     - Agent-ID: {agent_id}
     - Accept: text/event-stream
   ```

3. **URL参数认证**：
   ```
   URL: http://localhost:8000/mcp/sse/connect?agent_id={agent_id}&token={access_token}
   Headers:
     - Accept: text/event-stream
   ```

4. **混合认证（推荐）**：
   ```
   URL: http://localhost:34004/mcp/sse/connect?agent_id={agent_id}
   Headers:
     - Authorization: Bearer {access_token}
     - Accept: text/event-stream
   ```

#### 连接测试示例
```bash
# 基础连接测试
curl -N -H "Authorization: Bearer mcp_token_abc123def456..." \
     -H "Agent-ID: agent_001_claude_dev" \
     -H "Accept: text/event-stream" \
     http://localhost:34004/mcp/sse/connect

# URL参数方式
curl -N -H "Accept: text/event-stream" \
     "http://localhost:34004/mcp/sse/connect?agent_id=agent_001_claude_dev&token=mcp_token_abc123def456..."

# 验证连接状态
curl -H "Authorization: Bearer mcp_token_abc123def456..." \
     -H "Agent-ID: agent_001_claude_dev" \
     http://localhost:34004/mcp/agent/status
```

#### 连接状态码说明
- `200 OK` - 连接成功，开始SSE数据流
- `401 Unauthorized` - Token无效或过期
- `403 Forbidden` - Agent无权限或被禁用
- `404 Not Found` - Agent ID不存在
- `429 Too Many Requests` - 连接频率限制
- `500 Internal Server Error` - 服务器内部错误

### 4. 基本操作示例（带权限控制）

#### 创建项目（需要管理员权限）
```
请帮我创建一个新项目，名称为"AI助手开发"，描述为"开发一个智能客服AI助手"
```
*注意：只有具有`create_project`权限的Agent才能执行此操作*

#### 查看可访问的项目
```
请显示我可以访问的所有项目列表
```
*系统会根据Agent权限自动过滤项目列表*

#### 管理任务（权限控制）
```
请查看项目ID为3的所有待处理任务，并帮我认领优先级最高的任务
```
*只能访问Agent有权限的项目中的任务*

#### GitHub集成（项目权限验证）
```
请同步GitHub仓库 owner/repo 的所有Issues到项目3中，并创建对应的任务
```
*需要验证Agent对项目3的访问权限*

### 5. 权限管理示例

#### 申请项目访问权限
```
我需要访问项目ID为9的权限，请帮我申请
```

#### 查看当前权限
```
请显示我当前的权限和可访问的项目列表
```

#### 权限被拒绝的处理
```
# 当Agent尝试访问无权限的资源时
请查看项目ID为999的任务列表

# 系统响应
错误：访问被拒绝。您没有访问项目999的权限。
您当前可访问的项目：[1, 3, 5, 7]
如需申请权限，请联系管理员。
```

## 错误处理

### 常见错误码
- `1001` - 项目不存在
- `1002` - 任务已被认领
- `1003` - GitHub API访问失败
- `1004` - 权限不足
- `1005` - 参数验证失败

### 错误响应格式
```json
{
  "jsonrpc": "2.0",
  "error": {
    "code": 1001,
    "message": "Project not found",
    "data": {
      "project_id": 123
    }
  },
  "id": "request-id"
}
```

## 扩展开发

### 添加新的MCP工具
1. 创建工具类：`app/Mcp/Tools/YourTool.php`
2. 实现工具接口：`McpToolInterface`
3. 注册工具：在`McpServiceProvider`中注册
4. 添加测试：`tests/Feature/Mcp/YourToolTest.php`

### 添加新的MCP资源
1. 创建资源类：`app/Mcp/Resources/YourResource.php`
2. 实现资源接口：`McpResourceInterface`
3. 定义URI模式：在资源类中定义
4. 注册资源：在`McpServiceProvider`中注册

## 纯PHP SSE技术实现

### PHP SSE服务器架构
```php
// 基于Laravel + ReactPHP的异步SSE服务器
class McpSseServer
{
    private $loop;
    private $connections = [];
    private $agents = [];

    public function start($port = 8000)
    {
        $this->loop = \React\EventLoop\Factory::create();
        $socket = new \React\Socket\Server("0.0.0.0:$port", $this->loop);

        $socket->on('connection', function ($connection) {
            $this->handleConnection($connection);
        });

        $this->loop->run();
    }

    private function handleConnection($connection)
    {
        // 处理SSE连接、认证、权限验证
        // 实现心跳检测、消息推送等
    }
}
```

### SSE连接管理
- **连接池**：维护所有活跃的Agent连接
- **心跳检测**：30秒间隔检测连接状态
- **自动重连**：客户端断线自动重连机制
- **内存管理**：及时清理断开的连接

### 性能优化

#### 缓存策略
- 项目信息缓存：1小时
- 任务列表缓存：5分钟
- GitHub数据缓存：15分钟
- Agent状态缓存：1分钟
- SSE连接状态：实时更新

#### 并发处理
- 使用ReactPHP异步事件循环
- Laravel队列处理耗时操作
- 连接池管理多个MCP连接
- 异步处理GitHub API调用
- 非阻塞I/O操作

#### 内存优化
```php
// 内存使用监控
php artisan mcp:sse:memory-monitor

// 连接数限制
MCP_MAX_CONNECTIONS_PER_USER=10
MCP_MAX_TOTAL_CONNECTIONS=1000

// 自动垃圾回收
MCP_SSE_GC_INTERVAL=300
```

## 安全考虑

### 认证与授权
- 基于Token的API认证
- 细粒度权限控制
- 操作审计日志

### 数据保护
- 敏感数据加密存储
- API访问频率限制
- 输入数据验证与清理

## MCP工具详细说明

### 项目管理工具

#### create_project
**描述**：创建新项目
**参数**：
- `name` (string, required) - 项目名称
- `description` (string, optional) - 项目描述
- `timezone` (string, optional) - 项目时区，默认UTC
- `repositories` (array, optional) - 关联的代码仓库列表

**示例**：
```json
{
  "name": "create_project",
  "arguments": {
    "name": "AI助手开发",
    "description": "开发一个智能客服AI助手",
    "timezone": "Asia/Shanghai",
    "repositories": [
      "https://github.com/owner/ai-assistant"
    ]
  }
}
```

#### update_project
**描述**：更新项目信息
**参数**：
- `project_id` (integer, required) - 项目ID
- `name` (string, optional) - 项目名称
- `description` (string, optional) - 项目描述
- `timezone` (string, optional) - 项目时区
- `status` (string, optional) - 项目状态

### 任务管理工具

#### create_task
**描述**：创建新任务
**参数**：
- `title` (string, required) - 任务标题
- `description` (string, optional) - 任务描述
- `project_id` (integer, required) - 所属项目ID
- `priority` (string, optional) - 优先级：low, medium, high, urgent
- `labels` (array, optional) - 任务标签
- `due_date` (string, optional) - 截止日期

#### claim_task
**描述**：认领任务
**参数**：
- `task_id` (integer, required) - 任务ID
- `agent_id` (integer, required) - Agent ID

#### complete_task
**描述**：完成任务
**参数**：
- `task_id` (integer, required) - 任务ID
- `solution` (string, optional) - 解决方案描述
- `time_spent` (integer, optional) - 花费时间（分钟）

### GitHub集成工具 🔮 *后期扩展功能*

#### sync_github_issues
**描述**：同步GitHub Issues到项目任务
**参数**：
- `repository_url` (string, required) - GitHub仓库URL
- `project_id` (integer, required) - 目标项目ID
- `sync_mode` (string, optional) - 同步模式：all, open, closed

#### create_github_issue
**描述**：创建GitHub Issue
**参数**：
- `repository_url` (string, required) - GitHub仓库URL
- `title` (string, required) - Issue标题
- `body` (string, optional) - Issue内容
- `labels` (array, optional) - 标签列表
- `assignees` (array, optional) - 指派人员

## MCP资源详细说明

### 项目资源

#### project://list
**描述**：获取项目列表
**返回**：项目列表，包含基本信息

#### project://{id}
**描述**：获取特定项目详情
**参数**：
- `id` - 项目ID
**返回**：完整的项目信息，包括关联仓库、成员、统计数据

#### project://{id}/tasks
**描述**：获取项目任务列表
**参数**：
- `id` - 项目ID
- `status` (query) - 任务状态筛选
- `assignee` (query) - 指派人筛选
**返回**：任务列表

### 任务资源

#### task://list
**描述**：获取任务列表
**查询参数**：
- `project_id` - 项目ID筛选
- `status` - 状态筛选
- `priority` - 优先级筛选
- `assignee` - 指派人筛选
- `limit` - 返回数量限制
- `offset` - 分页偏移

#### task://{id}
**描述**：获取任务详情
**参数**：
- `id` - 任务ID
**返回**：完整的任务信息，包括评论、历史记录

### GitHub资源

#### github://repository/{owner}/{repo}
**描述**：获取GitHub仓库信息
**参数**：
- `owner` - 仓库所有者
- `repo` - 仓库名称
**返回**：仓库基本信息、统计数据

#### github://issues/{owner}/{repo}
**描述**：获取GitHub Issues列表
**参数**：
- `owner` - 仓库所有者
- `repo` - 仓库名称
**查询参数**：
- `state` - Issue状态：open, closed, all
- `labels` - 标签筛选
- `assignee` - 指派人筛选

## 通知系统

### 通知类型

#### task_status_changed
**触发条件**：任务状态发生变更
**数据结构**：
```json
{
  "type": "task_status_changed",
  "data": {
    "task_id": 123,
    "old_status": "pending",
    "new_status": "claimed",
    "changed_by": "agent_001",
    "timestamp": "2024-01-01T12:00:00Z"
  }
}
```

#### new_task_assigned
**触发条件**：新任务被分配给Agent
**数据结构**：
```json
{
  "type": "new_task_assigned",
  "data": {
    "task_id": 123,
    "agent_id": "agent_001",
    "project_id": 1,
    "priority": "high",
    "timestamp": "2024-01-01T12:00:00Z"
  }
}
```

#### github_issue_updated
**触发条件**：关联的GitHub Issue发生更新
**数据结构**：
```json
{
  "type": "github_issue_updated",
  "data": {
    "issue_number": 42,
    "repository": "owner/repo",
    "action": "closed",
    "task_id": 123,
    "timestamp": "2024-01-01T12:00:00Z"
  }
}
```

## 最佳实践

### 1. 项目组织
- 按功能模块组织项目
- 合理设置项目时区
- 定期清理已完成的任务
- 使用标签对任务进行分类

### 2. 任务管理
- 明确的任务标题和描述
- 合理设置任务优先级
- 及时更新任务状态
- 记录任务解决方案

### 3. GitHub集成 🔮 *后期扩展功能*
- 定期同步GitHub Issues
- 保持任务与Issue状态一致
- 使用标签进行分类管理
- 及时处理Webhook事件

### 4. Agent协作
- 合理分配任务给不同Agent
- 监控Agent工作状态
- 避免任务冲突
- 建立任务优先级机制

## 故障排查

### 常见问题

#### MCP连接失败
1. 检查MCP服务器是否正常启动
2. 验证客户端配置是否正确
3. 查看服务器日志排查错误
4. 确认网络连接正常

#### GitHub同步失败
1. 检查GitHub Token权限
2. 验证仓库访问权限
3. 查看API调用限制
4. 检查Webhook配置

#### 任务状态异常
1. 检查数据库连接
2. 验证任务状态流转逻辑
3. 查看相关日志
4. 检查并发操作冲突

### 日志分析
```bash
# 查看MCP服务器日志
tail -f storage/logs/mcp.log

# 查看GitHub集成日志 (后期扩展功能)
tail -f storage/logs/github.log

# 查看任务管理日志
tail -f storage/logs/tasks.log
```

---

**注意**：
- ✅ 已实现功能
- 🔄 开发中功能
- ❌ 计划中功能

**相关链接**：
- [MCP官方文档](https://modelcontextprotocol.io)
- [Laravel文档](https://laravel.com/docs)
- [GitHub API文档](https://docs.github.com/en/rest)