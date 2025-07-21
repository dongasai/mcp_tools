# 数据库连接模块 (Dbcont)

## 概述

数据库连接模块为 MCP Tools 项目提供安全的数据库访问和 SQL 执行能力。该模块允许 Agent 通过 MCP 协议安全地连接和操作外部数据库，支持细粒度的权限控制和审计日志。

## 核心功能

### 1. 数据库连接管理
- **多数据库支持**：SQLite、MySQL、MariaDB
- **连接池管理**：高效的连接复用和管理
- **连接状态监控**：实时监控连接健康状态
- **自动重连机制**：网络中断时的自动恢复

### 2. 权限控制系统
- **三级权限模式**：
  - **只读权限**：仅允许 SELECT 查询
  - **读写权限**：允许 SELECT、INSERT、UPDATE、DELETE
  - **管理权限**：允许所有 SQL 语句（包括 DDL）
- **项目级隔离**：数据库连接归属于特定项目
- **Agent 权限分配**：为不同 Agent 分配不同的数据库访问权限

### 3. SQL 执行引擎
- **安全执行**：SQL 注入防护和语句验证
- **结果格式化**：统一的查询结果格式
- **执行监控**：查询性能和执行时间统计
- **错误处理**：详细的错误信息和异常处理

## 架构设计

### 模块结构
```
app/Modules/Dbcont/
├── Controllers/
│   ├── DatabaseConnectionController.php    # 数据库连接管理
│   └── SqlExecutionController.php          # SQL执行控制器
├── Models/
│   ├── DatabaseConnection.php              # 数据库连接模型
│   └── SqlExecutionLog.php                 # SQL执行日志模型
├── Services/
│   ├── DatabaseConnectionService.php       # 连接管理服务
│   ├── SqlExecutionService.php             # SQL执行服务
│   ├── PermissionService.php               # 权限管理服务
│   └── SecurityService.php                 # 安全验证服务
├── Tools/
│   ├── SqlExecuteTool.php                  # SQL执行MCP工具
│   └── DatabaseQueryTool.php               # 数据库查询MCP工具
├── Resources/
│   └── DatabaseResource.php                # 数据库连接MCP资源
├── Middleware/
│   ├── DatabaseAccessMiddleware.php        # 数据库访问中间件
│   └── SqlValidationMiddleware.php         # SQL验证中间件
├── Enums/
│   ├── DatabaseType.php                    # 数据库类型枚举
│   ├── PermissionLevel.php                 # 权限级别枚举
│   └── ConnectionStatus.php                # 连接状态枚举
├── Events/
│   ├── DatabaseConnected.php               # 数据库连接事件
│   ├── SqlExecuted.php                     # SQL执行事件
│   └── PermissionViolation.php             # 权限违规事件
├── Exceptions/
│   ├── DatabaseConnectionException.php     # 数据库连接异常
│   ├── SqlExecutionException.php           # SQL执行异常
│   └── PermissionDeniedException.php       # 权限拒绝异常
├── Validation/
│   ├── DatabaseConnectionValidation.php    # 连接配置验证
│   └── SqlStatementValidation.php          # SQL语句验证
├── database/
│   └── migrations/
│       ├── create_database_connections_table.php
│       ├── create_agent_database_permissions_table.php
│       └── create_sql_execution_logs_table.php
├── config/
│   └── dbcont.php                          # 模块配置文件
└── routes/
    └── api.php                             # API路由定义
```

## 数据模型设计

### 数据库连接表 (database_connections)
```sql
- id: 主键
- project_id: 项目ID (外键)
- name: 连接名称
- type: 数据库类型 (SQLITE, MYSQL, MARIADB)
- host: 主机地址
- port: 端口号
- database: 数据库名
- username: 用户名
- password: 密码 (加密存储)
- options: 连接选项 (JSON)
- status: 连接状态 (ACTIVE, INACTIVE, ERROR)
- last_tested_at: 最后测试时间
- created_at: 创建时间
- updated_at: 更新时间
```

### Agent数据库权限表 (agent_database_permissions)
```sql
- id: 主键
- agent_id: Agent ID (外键)
- database_connection_id: 数据库连接ID (外键)
- permission_level: 权限级别 (READ_ONLY, READ_WRITE, ADMIN)
- allowed_tables: 允许访问的表 (JSON数组)
- denied_operations: 禁止的操作 (JSON数组)
- max_query_time: 最大查询时间 (秒)
- max_result_rows: 最大结果行数
- created_at: 创建时间
- updated_at: 更新时间
```

### SQL执行日志表 (sql_execution_logs)
```sql
- id: 主键
- agent_id: Agent ID (外键)
- database_connection_id: 数据库连接ID (外键)
- sql_statement: SQL语句
- execution_time: 执行时间 (毫秒)
- rows_affected: 影响行数
- result_size: 结果大小 (字节)
- status: 执行状态 (SUCCESS, ERROR, TIMEOUT)
- error_message: 错误信息
- ip_address: 客户端IP
- executed_at: 执行时间
```

## MCP 集成

### MCP 工具

#### 1. SQL执行工具 (sql_execute)
- **功能**：执行SQL语句并返回结果
- **权限验证**：基于Agent权限和数据库连接权限
- **参数**：
  - `connection_id`: 数据库连接ID
  - `sql`: SQL语句
  - `timeout`: 执行超时时间（可选）
- **返回**：查询结果或执行状态

#### 2. 数据库查询工具 (database_query)
- **功能**：提供结构化的数据库查询接口
- **权限验证**：基于表级权限控制
- **参数**：
  - `connection_id`: 数据库连接ID
  - `table`: 表名
  - `columns`: 查询字段（可选）
  - `where`: 查询条件（可选）
  - `limit`: 结果限制（可选）
- **返回**：格式化的查询结果

### MCP 资源

#### 数据库连接资源 (database://)
- **URI模式**：`database://{connection_id}/{path}`
- **支持的路径**：
  - `database://list` - 获取可用数据库连接列表
  - `database://{id}` - 获取特定数据库连接信息
  - `database://{id}/tables` - 获取数据库表列表
  - `database://{id}/schema/{table}` - 获取表结构信息
  - `database://{id}/status` - 获取连接状态

## 安全机制

### 1. 权限控制
- **多层权限验证**：Agent权限 → 项目权限 → 数据库权限 → 表权限
- **操作级权限**：细粒度控制每个SQL操作类型
- **时间限制**：查询执行时间和结果大小限制
- **IP白名单**：可配置允许访问的IP地址范围

### 2. SQL安全
- **SQL注入防护**：参数化查询和语句验证
- **危险操作检测**：识别和阻止潜在危险的SQL语句
- **语句白名单**：可配置允许的SQL语句模式
- **结果过滤**：敏感数据字段的自动过滤

### 3. 审计日志
- **完整记录**：所有数据库操作的详细日志
- **性能监控**：查询执行时间和资源使用统计
- **异常追踪**：错误和异常情况的详细记录
- **合规支持**：满足数据访问合规要求的审计功能

## 配置管理

### 模块配置 (config/dbcont.php)
```php
return [
    // 默认配置
    'default' => [
        'timeout' => 30,                    // 默认查询超时时间（秒）
        'max_result_rows' => 1000,          // 默认最大结果行数
        'max_result_size' => '10MB',        // 默认最大结果大小
    ],

    // 安全配置
    'security' => [
        'enable_sql_validation' => true,    // 启用SQL验证
        'allowed_operations' => [           // 允许的SQL操作
            'SELECT', 'INSERT', 'UPDATE', 'DELETE'
        ],
        'denied_keywords' => [              // 禁止的SQL关键字
            'DROP', 'TRUNCATE', 'ALTER'
        ],
        'enable_ip_whitelist' => false,     // 启用IP白名单
        'ip_whitelist' => [],               // IP白名单列表
    ],

    // 连接配置
    'connections' => [
        'pool_size' => 10,                  // 连接池大小
        'idle_timeout' => 300,              // 空闲连接超时时间（秒）
        'retry_attempts' => 3,              // 重连尝试次数
        'retry_delay' => 1000,              // 重连延迟时间（毫秒）
    ],

    // 日志配置
    'logging' => [
        'enable_query_log' => true,         // 启用查询日志
        'enable_performance_log' => true,   // 启用性能日志
        'log_slow_queries' => true,         // 记录慢查询
        'slow_query_threshold' => 1000,     // 慢查询阈值（毫秒）
    ],
];
```

## 使用示例

### 1. 创建数据库连接
```php
// 通过用户后台创建数据库连接
$connection = DatabaseConnection::create([
    'project_id' => 1,
    'name' => 'Production MySQL',
    'type' => DatabaseType::MYSQL,
    'host' => 'localhost',
    'port' => 3306,
    'database' => 'myapp',
    'username' => 'user',
    'password' => encrypt('password'),
    'status' => ConnectionStatus::ACTIVE,
]);
```

### 2. 分配Agent权限
```php
// 为Agent分配数据库访问权限
AgentDatabasePermission::create([
    'agent_id' => 1,
    'database_connection_id' => 1,
    'permission_level' => PermissionLevel::READ_WRITE,
    'allowed_tables' => ['users', 'orders', 'products'],
    'max_query_time' => 30,
    'max_result_rows' => 1000,
]);
```

### 3. MCP工具调用
```json
{
    "method": "tools/call",
    "params": {
        "name": "sql_execute",
        "arguments": {
            "connection_id": 1,
            "sql": "SELECT * FROM users WHERE status = 'active' LIMIT 10"
        }
    }
}
```

## 开发状态

### 当前状态
- **设计阶段**：✅ 完成 - 架构设计和文档编写
- **模型实现**：❌ 待开发 - 数据模型和迁移文件
- **服务层**：❌ 待开发 - 核心业务逻辑
- **MCP集成**：❌ 待开发 - MCP工具和资源
- **安全机制**：❌ 待开发 - 权限控制和安全验证
- **测试覆盖**：❌ 待开发 - 单元测试和集成测试

### 下一步计划
1. **创建数据库迁移文件**：定义数据表结构
2. **实现核心模型**：DatabaseConnection、AgentDatabasePermission、SqlExecutionLog
3. **开发服务层**：连接管理、SQL执行、权限验证服务
4. **集成MCP协议**：实现MCP工具和资源
5. **安全机制实现**：SQL验证、权限控制、审计日志
6. **编写测试用例**：确保功能正确性和安全性

## 技术依赖

### Laravel 组件
- **Eloquent ORM**：数据模型和关系管理
- **Database**：多数据库连接和查询构建器
- **Validation**：输入验证和数据验证
- **Encryption**：敏感数据加密存储
- **Events**：事件驱动的架构支持

### 第三方包
- **php-mcp/laravel**：MCP协议集成
- **inhere/php-validate**：数据验证
- **dcat/laravel-admin**：后台管理界面

### 安全考虑
- **参数化查询**：防止SQL注入攻击
- **权限验证**：多层权限控制机制
- **审计日志**：完整的操作记录
- **数据加密**：敏感信息的安全存储