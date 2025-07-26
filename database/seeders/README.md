# Database Seeders 文档

本目录包含了 MCP Tools 项目的数据库种子文件，用于初始化系统的基础数据和测试数据。


## Seeder内容概述
1. 管理员后台菜单
2. 用户后台菜单
3. MCP测试数据
  - 一个默认用户
  - 一个测试项目，属于默认用户
  - 两个测试Agent，`test-agent-001`，`test-agent-002`
4. 数据库测试数据
  - 默认数据库，驱动sqlite,目标 `database/database.sqlite`
  - 给Agent`test-agent-001`，赋予所有权限


## Seeder 文件说明

### 1. DatabaseSeeder.php
**主要入口文件**，定义了所有 Seeder 的执行顺序：

```php
$this->call(AdminTablesSeeder::class);        // 管理员后台基础数据
$this->call(UserAdminMenuSeeder::class);      // 用户后台菜单
$this->call(MCPTestDataSeeder::class);        // MCP测试数据
$this->call(DatabaseConnectionSeeder::class); // 数据库连接
```

### 2. AdminTablesSeeder.php
**管理员后台基础数据初始化**

#### 创建的数据：
- **管理员账户**：
  - 用户名：`admin`
  - 密码：`admin`
  - 角色：Administrator

- **权限系统**：
  - Auth management (认证管理)
  - Users (用户管理)
  - Roles (角色管理) 
  - Permissions (权限管理)
  - Menu (菜单管理)
  - Extension (扩展管理)

- **后台菜单结构**：
  ```
  工作台 (/)
  系统管理
    ├── 管理员 (auth/users)
    ├── 角色 (auth/roles)
    ├── 权限 (auth/permissions)
    └── 菜单 (auth/menu)
  项目管理
    ├── 用户管理 (users)
    ├── 项目列表 (projects)
    └── 任务管理 (tasks)
  Agent管理
    ├── Agent列表 (agents)
    └── 问题管理 (questions)
  开发工具
    └── 扩展 (auth/extensions)
  ```

### 3. UserAdminMenuSeeder.php
**用户后台菜单初始化**

#### 创建的菜单项：
```
仪表板 (/)
项目管理 (projects)
任务管理 (tasks)
Agent管理 (agents)
问题管理 (questions)
个人设置 (profile)
GitHub集成 (github)
开发工具 (#)
  ├── 用户管理 (users)
  └── 任务评论 (task-comments)
```

#### 特点：
- 使用 Feather 图标系统
- 支持层级菜单结构
- 自动设置创建和更新时间

### 4. McpTestDataSeeder.php
**MCP 系统测试数据**

#### 创建的测试数据：

**测试用户**：
- 姓名：Test User
- 用户名：testuser
- 邮箱：test@example.com
- 密码：password
- 时区：Asia/Shanghai

**默认项目**：
- 名称：Default Project
- 描述：MCP 测试默认项目
- 状态：active
- 设置：自动同步、通知开启

**测试 Agent**：

*Agent 1*：
- 标识符：test-agent-001
- 名称：Test Agent 1
- Token：123456
- 功能：任务管理、项目查询、资源访问
- 模型：claude-3.5 (4000 tokens)

*Agent 2*：
- 标识符：test-agent-002  
- 名称：Test Agent 2
- Token：789012
- 功能：代码分析、项目查询、资源访问
- 模型：claude-3.5 (8000 tokens)

### 5. DatabaseConnectionSeeder.php
**数据库连接配置**

#### 创建的连接：
- **名称**：默认SQLite数据库
- **驱动**：sqlite
- **数据库文件**：database/database.sqlite
- **默认连接**：是

#### Agent 权限配置：
为第一个 Agent 自动授予以下权限：
- can_select: true (查询权限)
- can_insert: true (插入权限)
- can_update: true (更新权限)
- can_delete: true (删除权限)
- can_execute: true (执行权限)

## 使用方法

### 执行所有 Seeders
```bash
php artisan db:seed
```

### 执行特定 Seeder
```bash
php artisan db:seed --class=AdminTablesSeeder
php artisan db:seed --class=UserAdminMenuSeeder
php artisan db:seed --class=MCPTestDataSeeder
php artisan db:seed --class=DatabaseConnectionSeeder
```

### 重置并重新填充数据库
```bash
php artisan migrate:fresh --seed
```

## 注意事项

1. **执行顺序**：Seeders 有依赖关系，必须按照 DatabaseSeeder 中定义的顺序执行
2. **重复执行**：部分 Seeder 使用 `firstOrCreate` 或检查机制防止重复创建数据
3. **测试环境**：这些 Seeders 主要用于开发和测试环境，生产环境需要谨慎使用
4. **密码安全**：测试数据中的密码仅用于开发测试，生产环境应使用强密码

## 数据依赖关系

```
AdminTablesSeeder (基础权限系统)
    ↓
UserAdminMenuSeeder (用户后台菜单)
    ↓  
MCPTestDataSeeder (用户、项目、Agent)
    ↓
DatabaseConnectionSeeder (数据库连接和权限)
```

## 相关文件

- 模型文件：`app/Models/`, `app/Modules/*/Models/`
- 迁移文件：`database/migrations/`
- 配置文件：`config/admin.php`, `config/user-admin.php`
