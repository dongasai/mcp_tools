# Dbcont 模块 - 数据库连接管理

## 概述

Dbcont模块是一个内部服务模块，提供数据库连接管理和SQL执行功能。该模块不对外暴露API，仅通过Service调用使用。

## 功能特性

- **多数据库支持**: 支持SQLite、MySQL、MariaDB
- **权限控制**: 三级权限系统（READ_ONLY、READ_WRITE、ADMIN）
- **安全验证**: SQL注入防护、权限验证、审计日志
- **连接管理**: 连接测试、状态监控、配置管理
- **执行日志**: 完整的SQL执行历史记录
- **操作日志**: 符合AiWork规范的操作审计日志

## 数据库表结构

### 1. database_connections
存储数据库连接配置信息

| 字段 | 类型 | 描述 |
|------|------|------|
| id | bigint | 主键 |
| name | string | 连接名称 |
| type | enum | 数据库类型(SQLITE, MYSQL, MARIADB) |
| host | string | 主机地址 |
| port | int | 端口号 |
| database | string | 数据库名称 |
| username | string | 用户名 |
| password | string | 密码 |
| options | json | 连接选项 |
| status | enum | 连接状态(ACTIVE, INACTIVE) |
| created_at | timestamp | 创建时间 |
| updated_at | timestamp | 更新时间 |

### 2. agent_database_permissions
存储Agent对数据库连接的权限配置

| 字段 | 类型 | 描述 |
|------|------|------|
| id | bigint | 主键 |
| agent_id | bigint | Agent ID |
| database_connection_id | bigint | 数据库连接ID |
| permission_level | enum | 权限级别(READ_ONLY, READ_WRITE, ADMIN) |
| created_at | timestamp | 创建时间 |
| updated_at | timestamp | 更新时间 |

### 3. sql_execution_logs
存储SQL执行日志

| 字段 | 类型 | 描述 |
|------|------|------|
| id | bigint | 主键 |
| database_connection_id | bigint | 数据库连接ID |
| agent_id | bigint | Agent ID |
| sql_statement | text | SQL语句 |
| success | boolean | 执行是否成功 |
| error_message | text | 错误信息 |
| execution_time | decimal | 执行时间(秒) |
| affected_rows | int | 影响行数 |
| created_at | timestamp | 创建时间 |

## 操作日志规范

所有关键操作（创建连接、测试连接、执行SQL等）都会记录到`AiWork/年月/日时分-任务标题.md`文件中，格式如下：

```markdown
# 操作标题

**模块**: Dbcont
**时间**: YYYY-MM-DD HH:MM:SS

## 操作详情
```yaml
action: 操作类型
connection_id: 连接ID
agent_id: AgentID
status: 执行状态
...
```

示例文件路径：`AiWork/2025年07月/241047-测试数据库连接成功.md`

## 权限级别说明

- **READ_ONLY**: 只读权限，只能执行SELECT查询
- **READ_WRITE**: 读写权限，可以执行SELECT、INSERT、UPDATE、DELETE
- **ADMIN**: 管理员权限，可以执行所有SQL语句包括DDL

## 使用方法

### 1. 创建数据库连接

```php
use Modules\Dbcont\Services\DatabaseConnectionService;
use Modules\Dbcont\Enums\DatabaseType;

$service = app(DatabaseConnectionService::class);

$connection = $service->createConnection([
    'name' => 'MySQL Production',
    'type' => DatabaseType::MYSQL,
    'host' => 'localhost',
    'port' => 3306,
    'database' => 'production',
    'username' => 'user',
    'password' => 'password',
    'options' => [
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
    ],
]);
```

### 2. 授予权限

```php
use Modules\Dbcont\Services\PermissionService;
use Modules\Dbcont\Enums\PermissionLevel;

$service = app(PermissionService::class);

$service->grantPermission(
    $agentId,
    $connectionId,
    PermissionLevel::READ_WRITE
);
```

### 3. 执行SQL

```php
use Modules\Dbcont\Services\SqlExecutionService;

$service = app(SqlExecutionService::class);

$result = $service->executeSql(
    $connectionId,
    $agentId,
    'SELECT * FROM users WHERE active = 1'
);

if ($result['success']) {
    $data = $result['data'];
    // 处理查询结果
} else {
    $error = $result['message'];
    // 处理错误
}
```

### 4. 测试连接

```php
$result = $service->testConnection($connection);
```

## 安全特性

1. **SQL注入防护**: 使用参数化查询和严格的输入验证
2. **操作审计**: 所有关键操作记录到AiWork日志文件
2. **权限验证**: 每个SQL执行前都会检查权限
3. **审计日志**: 所有SQL执行都会被记录
4. **连接加密**: 敏感信息加密存储
5. **操作限制**: 基于权限级别的操作限制

## 事件系统

模块使用Laravel事件系统进行解耦：

- `DatabaseConnected`: 数据库连接成功时触发
- `SqlExecuted`: SQL执行完成时触发
- `PermissionViolation`: 权限验证失败时触发

## 配置

配置文件位于 `app/Modules/Dbcont/config/dbcont.php`

```php
return [
    'max_connections_per_agent' => 10,
    'max_execution_time' => 30,
    'max_result_rows' => 1000,
    'log_retention_days' => 30,
    'security' => [
        'enable_sql_validation' => true,
        'enable_injection_detection' => true,
        'enable_dangerous_operation_detection' => true,
    ],
];
```

## 测试

运行模块测试：

```bash
php artisan test tests/Unit/Modules/Dbcont/
```

## 数据库迁移

运行迁移创建必要的数据表：

```bash
php artisan migrate --path=app/Modules/Dbcont/database/migrations
```

## 操作日志示例

```php
use Modules\Dbcont\Services\OperationLogService;

$logService = app(OperationLogService::class);

// 记录创建连接操作
$logService->log('创建数据库连接', [
    'action' => 'create_connection',
    'connection_id' => 123,
    'name' => 'Production DB',
    'type' => 'MySQL',
    'database' => 'production'
]);

// 记录SQL执行操作
$logService->log('执行SQL查询', [
    'action' => 'execute_query',
    'connection_id' => 123,
    'agent_id' => 456,
    'sql' => 'SELECT * FROM users',
    'status' => 'success',
    'execution_time' => 42
]);
```

## 注意事项

1. 该模块为内部服务模块，不对外提供API
2. 所有数据库连接配置需要管理员手动设置
3. Agent权限需要显式授予
4. 生产环境建议使用SSL连接
5. 定期清理执行日志以节省存储空间

## 故障排除

### 连接失败
- 检查数据库配置是否正确
- 确认网络连接正常
- 验证用户名密码

### 权限错误
- 确认Agent已授予相应权限
- 检查权限级别是否足够
- 验证连接状态是否为ACTIVE

### SQL执行错误
- 检查SQL语法
- 确认表和字段存在
- 查看执行日志获取详细信息