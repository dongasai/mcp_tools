# `php artisan migrate:fresh --seed` 执行顺序详解

## 命令完整执行流程

当执行 `php artisan migrate:fresh --seed` 时，Laravel 按以下顺序执行：

### 第一阶段：数据库重置 (migrate:fresh)
```bash
1. 删除所有数据库表 (DROP ALL TABLES)
2. 重新运行所有迁移文件 (按时间戳顺序)
```

### 第二阶段：数据填充 (--seed)
```bash
3. 执行 DatabaseSeeder::run() 方法
4. 按 DatabaseSeeder 中定义的顺序执行各个 Seeder
```

## 详细执行步骤

### 步骤1: 删除所有表
```sql
-- Laravel 会执行类似这样的 SQL
DROP TABLE IF EXISTS `migrations`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `projects`;
-- ... 删除所有现有表
```

### 步骤2: 按时间戳顺序执行迁移文件
```
2024_12_01_000003_update_users_table.php
2025_05_28_033323_create_projects_table.php
2025_05_28_033329_create_tasks_table.php
2025_06_19_024522_create_agents_table.php
2025_07_07_182406_create_mcp_sessions_table.php
2025_07_07_193736_create_user_admin_tables.php
2025_07_07_202219_add_repository_url_to_projects_table.php
2025_07_08_122350_create_project_members_table.php
2025_07_19_144433_create_task_comments_table.php
2025_07_20_094256_create_agent_questions_table.php
2025_07_20_232828_add_agent_foreign_key_to_tasks_table.php
2025_07_21_074042_fix_agent_project_relationship.php
2025_07_22_103818_modify_question_type_field_in_agent_questions_table.php
2025_07_24_134200_create_database_connections_table.php
2025_07_24_134300_create_agent_database_permissions_table.php
2025_07_24_134400_create_sql_execution_logs_table.php
2025_07_25_133628_create_mcp_sessions_table.php
2025_07_25_190253_create_agent_tasks_table.php
```

**迁移文件执行顺序规则**：
- 按文件名中的时间戳排序 (YYYY_MM_DD_HHMMSS)
- 时间戳越早的越先执行
- 这确保了表结构的依赖关系正确建立

### 步骤3: 执行 DatabaseSeeder
```php
// database/seeders/DatabaseSeeder.php
public function run(): void
{
    $this->call(AdminTablesSeeder::class);        // 第1个
    $this->call(UserAdminMenuSeeder::class);      // 第2个
    $this->call(McpTestDataSeeder::class);        // 第3个
    $this->call(DatabaseConnectionSeeder::class); // 第4个
}
```

## 为什么这个顺序很重要？

### 迁移文件的依赖关系
```
users 表 → projects 表 (外键依赖)
projects 表 → tasks 表 (外键依赖)
users 表 → agents 表 (外键依赖)
agents 表 → agent_database_permissions 表 (外键依赖)
```

### Seeder 的依赖关系
```
AdminTablesSeeder → 创建基础用户和权限
    ↓
UserAdminMenuSeeder → 创建菜单 (依赖权限系统)
    ↓
McpTestDataSeeder → 创建测试用户和 Agent (依赖 users 和 agents 表)
    ↓
DatabaseConnectionSeeder → 授权 Agent (依赖 Agent 存在)
```

## 实际执行示例

让我们看一个完整的执行过程：

```bash
$ php artisan migrate:fresh --seed

# 第一阶段：删除和重建表结构
Dropped all tables successfully.
Migration table created successfully.
Migrating: 2024_12_01_000003_update_users_table
Migrated:  2024_12_01_000003_update_users_table (153.34ms)
Migrating: 2025_05_28_033323_create_projects_table
Migrated:  2025_05_28_033323_create_projects_table (47.76ms)
# ... 其他迁移文件

# 第二阶段：数据填充
Seeding: Database\Seeders\AdminTablesSeeder
Seeded:  Database\Seeders\AdminTablesSeeder (544ms)
Seeding: Database\Seeders\UserAdminMenuSeeder
用户后台菜单创建完成！
Seeded:  Database\Seeders\UserAdminMenuSeeder (100ms)
Seeding: Database\Seeders\McpTestDataSeeder
创建默认用户: Test User (test@example.com)
创建默认项目: Default Project
创建默认 Agent 1: Test Agent 1 (Token: 123456)
创建默认 Agent 2: Test Agent 2 (Token: 789012)
Seeded:  Database\Seeders\McpTestDataSeeder (200ms)
Seeding: Database\Seeders\DatabaseConnectionSeeder
Seeded:  Database\Seeders\DatabaseConnectionSeeder (50ms)

Database seeding completed successfully.
```

## 关键点总结

### 1. 迁移文件顺序
- **自动排序**：Laravel 根据文件名时间戳自动排序
- **不可控制**：开发者无法改变迁移文件的执行顺序（除非修改时间戳）
- **依赖管理**：通过合理的时间戳命名确保依赖关系

### 2. Seeder 顺序
- **手动控制**：在 `DatabaseSeeder::run()` 中手动定义顺序
- **同步执行**：每个 Seeder 必须完全执行完毕才执行下一个
- **依赖保证**：通过正确的调用顺序确保数据依赖关系

### 3. 整体流程
```
删除所有表 → 重建表结构 → 填充基础数据 → 填充业务数据
```

### 4. 失败处理
- 如果迁移失败：停止执行，不会进入 Seeder 阶段
- 如果 Seeder 失败：停止执行，已执行的 Seeder 数据保留

## 最佳实践

### 1. 迁移文件命名
```bash
# 好的做法：按逻辑依赖关系安排时间
2025_01_01_000001_create_users_table.php
2025_01_01_000002_create_projects_table.php
2025_01_01_000003_create_tasks_table.php
```

### 2. Seeder 设计
```php
// 好的做法：按依赖关系排序
public function run(): void
{
    // 基础数据优先
    $this->call(UsersSeeder::class);
    $this->call(RolesSeeder::class);
    
    // 配置数据其次
    $this->call(MenuSeeder::class);
    
    // 业务数据最后
    $this->call(ProjectsSeeder::class);
    $this->call(TasksSeeder::class);
}
```

这样就确保了整个 `migrate:fresh --seed` 过程的正确执行顺序！
