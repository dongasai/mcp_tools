# Database Seeders 文档

本目录包含了 MCP Tools 项目的数据库种子文件，用于初始化系统的基础数据和测试数据。


## Seeder内容概述
1. 管理员后台菜单
2. 用户后台菜单
3. MCP测试数据
  - 一个默认用户
  - 一个测试项目，属于默认用户
  - 两个测试Agent，`test-agent-001`，`test-agent-002`
4. 数据库连接配置
  - 主数据库，驱动sqlite,目标 `database/database.sqlite`
  - 给Agent`test-agent-001`，赋予读写权限
5. 演示数据库配置
  - 演示用户和项目
  - 三个演示Agent，具有不同权限级别
  - 演示数据库，驱动sqlite,目标 `database/database.demo.sqlite`
  - 分别给予只读、读写、管理员权限


## Seeder 文件说明

### 1. DatabaseSeeder.php
**主要入口文件**，定义了所有 Seeder 的执行顺序：

```php
$this->call(AdminTablesSeeder::class);        // 管理员后台基础数据
$this->call(UserAdminMenuSeeder::class);      // 用户后台菜单
$this->call(MCPTestDataSeeder::class);        // MCP测试数据
$this->call(DatabaseConnectionSeeder::class); // 数据库连接
$this->call(DemoDataSeeder::class);           // 演示数据库
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

### 4. MCPTestDataSeeder.php
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
**主数据库连接配置**

#### 创建的连接：
- **名称**：SQLite测试连接
- **驱动**：sqlite
- **数据库文件**：database/database.sqlite
- **状态**：active

#### Agent 权限配置：
为第一个 Agent (test-agent-001) 自动授予以下权限：
- **权限级别**：READ_WRITE (读写权限)
- **最大查询时间**：300秒
- **最大结果行数**：1000行

### 6. DemoDataSeeder.php
**演示数据库连接配置**

#### 创建的用户和项目：
**演示用户**：
- 姓名：Demo User
- 用户名：demouser
- 邮箱：demo@example.com
- 密码：demo123
- 时区：Asia/Shanghai

**演示项目**：
- 名称：Demo Project
- 描述：演示项目 - 用于测试多个Agent和权限配置
- 状态：active
- 设置：自动同步关闭、通知开启

#### 创建的连接：
- **名称**：SQLite演示连接
- **驱动**：sqlite
- **数据库文件**：database/database.demo.sqlite
- **状态**：active

#### 创建的演示 Agent：

**Demo Agent 1 (demo-agent-001)**：
- 名称：Demo Agent 1 - Read Only
- Token：demo001
- 功能：数据查询、报告生成
- 模型：claude-3.5 (2000 tokens)
- 权限级别：READ_ONLY (只读权限)
- 最大查询时间：60秒
- 最大结果行数：100行

**Demo Agent 2 (demo-agent-002)**：
- 名称：Demo Agent 2 - Read Write
- Token：demo002
- 功能：数据管理、任务自动化
- 模型：claude-3.5 (4000 tokens)
- 权限级别：READ_WRITE (读写权限)
- 最大查询时间：300秒
- 最大结果行数：1000行

**Demo Agent 3 (demo-agent-003)**：
- 名称：Demo Agent 3 - Full Access
- Token：demo003
- 功能：完整数据库访问、系统管理
- 模型：claude-3.5 (8000 tokens)
- 权限级别：ADMIN (管理员权限)
- 最大查询时间：600秒
- 最大结果行数：5000行

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
php artisan db:seed --class=DemoDataSeeder
```

### 重置并重新填充数据库
```bash
php artisan migrate:fresh --seed
```

## 注意事项

1. **执行顺序**：Seeders 有依赖关系，必须按照 DatabaseSeeder 中定义的顺序执行
2. **重复执行**：部分 Seeder 使用 `firstOrCreate` 或检查机制防止重复创建数据
3. **唯一标识符**：所有 Seeder 都使用唯一标识符（如 email、username、identifier）而非依赖顺序获取数据，确保数据一致性
4. **测试环境**：这些 Seeders 主要用于开发和测试环境，生产环境需要谨慎使用
5. **密码安全**：测试数据中的密码仅用于开发测试，生产环境应使用强密码

## 数据依赖关系

```
AdminTablesSeeder (基础权限系统)
    ↓
UserAdminMenuSeeder (用户后台菜单)
    ↓
MCPTestDataSeeder (用户、项目、Agent)
    ↓
DatabaseConnectionSeeder (主数据库连接和权限)
    ↓
DemoDataSeeder (演示数据库连接和权限)
```

## 唯一标识符使用

为了确保数据一致性和避免依赖执行顺序，所有 Seeder 都使用唯一标识符来查找和关联数据：

### 用户标识
- **测试用户**：使用 `email = 'test@example.com'` 作为唯一标识
- **演示用户**：使用 `email = 'demo@example.com'` 作为唯一标识
- **管理员用户**：使用 `username = 'admin'` 作为唯一标识

### 项目标识
- **默认项目**：使用 `name = 'Default Project'` 和 `user_id` 组合作为唯一标识
- **演示项目**：使用 `name = 'Demo Project'` 和 `user_id` 组合作为唯一标识

### Agent 标识
- **测试 Agent 1**：使用 `identifier = 'test-agent-001'` 作为唯一标识
- **测试 Agent 2**：使用 `identifier = 'test-agent-002'` 作为唯一标识
- **演示 Agent 1**：使用 `identifier = 'demo-agent-001'` 作为唯一标识
- **演示 Agent 2**：使用 `identifier = 'demo-agent-002'` 作为唯一标识
- **演示 Agent 3**：使用 `identifier = 'demo-agent-003'` 作为唯一标识

### 角色标识
- **管理员角色**：使用 `slug = Role::ADMINISTRATOR` 作为唯一标识

### 数据库连接标识
- **主数据库**：使用 `name = 'SQLite测试连接'` 作为唯一标识
- **演示数据库**：使用 `name = 'SQLite演示连接'` 作为唯一标识

这种设计确保了即使在不同的执行环境或顺序下，Seeder 都能正确找到和关联相关数据。

## 相关文件

- 模型文件：`app/Models/`, `app/Modules/*/Models/`
- 迁移文件：`database/migrations/`
- 配置文件：`config/admin.php`, `config/user-admin.php`
