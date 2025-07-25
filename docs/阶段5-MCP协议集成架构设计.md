# 阶段5: MCP协议集成架构设计

**文档版本**: v1.0  
**创建时间**: 2025年07月08日  
**状态**: 架构设计阶段  

## 1. 概述

### 1.1 目标
实现基于Server-Sent Events (SSE)的Model Context Protocol (MCP)服务，为Agent提供实时任务处理和通信能力。

### 1.2 核心特性
- **基于SSE的实时通信**: 使用Streamable HTTP transport
- **Agent访问控制**: 项目级权限和身份识别
- **任务分发系统**: 实时任务分配和状态同步
- **JSON-RPC 2.0协议**: 标准化消息格式

## 2. MCP协议架构

### 2.1 协议层次结构
```
┌─────────────────────────────────────┐
│           MCP Application           │  ← 业务逻辑层
├─────────────────────────────────────┤
│           MCP Protocol              │  ← 协议处理层
├─────────────────────────────────────┤
│        Streamable HTTP              │  ← 传输层
├─────────────────────────────────────┤
│         JSON-RPC 2.0                │  ← 消息格式层
└─────────────────────────────────────┘
```

### 2.2 通信模式
1. **Client-to-Server**: HTTP POST请求发送JSON-RPC消息
2. **Server-to-Client**: SSE流推送实时消息
3. **会话管理**: 使用`MCP-Session-Id`头维护状态

## 3. 系统架构设计

### 3.1 模块结构
```
app/Modules/MCP/
├── Controllers/
│   ├── MCPServerController.php      # MCP服务端点
│   ├── AgentAuthController.php      # Agent认证
│   └── TaskDistributionController.php # 任务分发
├── Services/
│   ├── MCPProtocolService.php       # 协议处理
│   ├── SseStreamService.php         # SSE流管理
│   ├── AgentSessionService.php      # 会话管理
│   └── TaskDispatchService.php      # 任务调度
├── Models/
│   ├── AgentSession.php             # Agent会话
│   ├── MCPMessage.php               # MCP消息
│   └── TaskAssignment.php           # 任务分配
├── Middleware/
│   ├── MCPAuthMiddleware.php        # MCP认证
│   └── AgentAccessMiddleware.php    # Agent访问控制
└── Events/
    ├── AgentConnected.php           # Agent连接事件
    ├── TaskAssigned.php             # 任务分配事件
    └── TaskCompleted.php            # 任务完成事件
```

### 3.2 数据库设计
```sql
-- Agent会话表
CREATE TABLE agent_sessions (
    id BIGINT PRIMARY KEY,
    agent_id BIGINT NOT NULL,
    session_id VARCHAR(255) UNIQUE NOT NULL,
    project_id BIGINT,
    status ENUM('active', 'inactive', 'expired'),
    capabilities JSON,
    last_heartbeat TIMESTAMP,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (agent_id) REFERENCES agents(id),
    FOREIGN KEY (project_id) REFERENCES projects(id)
);

-- MCP消息日志表
CREATE TABLE mcp_messages (
    id BIGINT PRIMARY KEY,
    session_id VARCHAR(255) NOT NULL,
    direction ENUM('inbound', 'outbound'),
    message_type ENUM('request', 'response', 'notification'),
    method VARCHAR(100),
    message_data JSON,
    created_at TIMESTAMP,
    INDEX idx_session_id (session_id),
    INDEX idx_created_at (created_at)
);

-- 任务分配表
CREATE TABLE task_assignments (
    id BIGINT PRIMARY KEY,
    task_id BIGINT NOT NULL,
    agent_session_id BIGINT NOT NULL,
    status ENUM('assigned', 'in_progress', 'completed', 'failed'),
    assigned_at TIMESTAMP,
    started_at TIMESTAMP,
    completed_at TIMESTAMP,
    result JSON,
    FOREIGN KEY (task_id) REFERENCES tasks(id),
    FOREIGN KEY (agent_session_id) REFERENCES agent_sessions(id)
);
```

## 4. 核心服务实现

### 4.1 MCP服务端点
```php
// app/Modules/MCP/Controllers/MCPServerController.php
class MCPServerController extends Controller
{
    public function handleMCPRequest(Request $request)
    {
        // 处理HTTP POST的JSON-RPC请求
        $jsonRpcMessage = $request->json()->all();
        
        // 验证JSON-RPC格式
        $this->validateJsonRpc($jsonRpcMessage);
        
        // 处理不同类型的MCP消息
        switch ($jsonRpcMessage['method']) {
            case 'initialize':
                return $this->handleInitialize($request, $jsonRpcMessage);
            case 'tasks/list':
                return $this->handleTasksList($jsonRpcMessage);
            case 'tasks/execute':
                return $this->handleTaskExecute($jsonRpcMessage);
            default:
                return $this->methodNotFound($jsonRpcMessage['id']);
        }
    }
    
    public function handleSseStream(Request $request)
    {
        // 处理HTTP GET的SSE流连接
        return response()->stream(function () use ($request) {
            $sessionId = $request->header('MCP-Session-Id');
            $this->sseStreamService->streamToAgent($sessionId);
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
        ]);
    }
}
```

### 4.2 SSE流服务
```php
// app/Modules/MCP/Services/SseStreamService.php
class SseStreamService
{
    public function streamToAgent(string $sessionId): void
    {
        // 设置无限执行时间
        set_time_limit(0);
        
        while (true) {
            // 检查会话是否仍然活跃
            if (!$this->isSessionActive($sessionId)) {
                break;
            }
            
            // 获取待发送的消息
            $messages = $this->getPendingMessages($sessionId);
            
            foreach ($messages as $message) {
                $this->sendSseEvent($message);
            }
            
            // 发送心跳
            $this->sendHeartbeat();
            
            // 短暂休眠
            usleep(100000); // 100ms
        }
    }
    
    private function sendSseEvent(array $data): void
    {
        echo "data: " . json_encode($data) . "\n\n";
        ob_flush();
        flush();
    }
}
```

## 5. Agent认证与访问控制

### 5.1 认证流程
1. **Agent注册**: Agent首次连接时注册身份信息
2. **令牌生成**: 服务器生成访问令牌和会话ID
3. **权限验证**: 验证Agent对特定项目的访问权限
4. **会话维护**: 通过心跳机制维护会话状态

### 5.2 访问控制矩阵
```
Agent权限级别:
- READ: 只能读取任务信息
- EXECUTE: 可以执行分配的任务
- MANAGE: 可以创建和管理任务
- ADMIN: 完全访问项目资源
```

## 6. 任务分发机制

### 6.1 分发策略
- **负载均衡**: 根据Agent能力和当前负载分配任务
- **优先级调度**: 高优先级任务优先分配
- **能力匹配**: 根据任务要求匹配Agent能力
- **故障转移**: Agent失联时重新分配任务

### 6.2 状态同步
- **实时状态**: 通过SSE推送任务状态变更
- **进度报告**: Agent定期报告任务执行进度
- **结果收集**: 任务完成后收集执行结果

## 7. 消息格式规范

### 7.1 JSON-RPC消息结构
```json
{
  "jsonrpc": "2.0",
  "id": "unique-request-id",
  "method": "tasks/execute",
  "params": {
    "task_id": 123,
    "parameters": {...}
  }
}
```

### 7.2 MCP特定消息类型
- **initialize**: 初始化连接
- **tasks/list**: 获取任务列表
- **tasks/execute**: 执行任务
- **tasks/status**: 更新任务状态
- **heartbeat**: 心跳消息

## 8. 安全考虑

### 8.1 传输安全
- **HTTPS强制**: 生产环境必须使用HTTPS
- **Origin验证**: 验证请求来源防止DNS重绑定攻击
- **会话安全**: 使用加密安全的会话ID

### 8.2 访问控制
- **令牌验证**: 每个请求验证访问令牌
- **权限检查**: 细粒度的操作权限控制
- **审计日志**: 记录所有MCP操作用于审计

## 9. 性能优化

### 9.1 连接管理
- **连接池**: 管理Agent连接池
- **心跳机制**: 定期检查连接健康状态
- **超时处理**: 合理的连接和请求超时设置

### 9.2 消息处理
- **异步处理**: 使用队列处理耗时操作
- **批量操作**: 支持批量任务分配和状态更新
- **缓存策略**: 缓存频繁访问的数据

## 10. 监控与调试

### 10.1 监控指标
- **连接数**: 活跃Agent连接数
- **消息吞吐**: 每秒处理的消息数
- **任务执行**: 任务成功率和平均执行时间
- **错误率**: 各类错误的发生频率

### 10.2 调试工具
- **消息日志**: 详细的MCP消息日志
- **连接状态**: 实时的Agent连接状态
- **性能分析**: 请求处理时间分析

## 11. 实施计划

### 11.1 第一阶段 (本周)
1. 创建MCP模块基础结构
2. 实现基本的JSON-RPC消息处理
3. 开发SSE流服务
4. 创建Agent认证机制

### 11.2 第二阶段 (下周)
1. 实现任务分发系统
2. 开发会话管理功能
3. 添加访问控制和权限验证
4. 创建监控和日志系统

### 11.3 第三阶段 (测试阶段)
1. 集成测试和性能测试
2. 安全性测试和漏洞扫描
3. 文档完善和部署指南
4. 生产环境部署准备

---

**下一步**: 开始实施第一阶段，创建MCP模块基础结构和核心服务。
