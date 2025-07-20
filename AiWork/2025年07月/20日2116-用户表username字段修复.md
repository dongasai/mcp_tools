# 用户表username字段修复

**时间**: 2025年07月20日 21:16  
**任务**: 修复用户表缺少username字段，不符合dcat-admin要求的问题

## 问题描述

用户表缺少`username`字段，导致dcat-admin认证系统无法正常工作。dcat-admin默认期望用户表有`username`字段用于登录认证。

## 解决方案

### 1. 添加username字段到users表

创建迁移文件：`2025_07_20_132130_add_username_to_users_table.php`

```php
// 添加username字段，先允许为空
$table->string('username')->nullable()->after('email');

// 为现有用户设置username为email
DB::statement('UPDATE users SET username = email WHERE username IS NULL OR username = ""');

// 然后设置字段为NOT NULL和UNIQUE
$table->string('username')->nullable(false)->unique()->change();
```

### 2. 修复user_admin_menu表字段缺失

发现`user_admin_menu`表缺少`show`和`extension`字段，导致菜单seed迁移失败。

创建迁移文件：`2025_07_20_131817_add_missing_fields_to_user_admin_menu_table.php`

```php
// 添加缺失的字段
$table->tinyInteger('show')->default(1)->after('uri');
$table->string('extension', 50)->default('')->after('show');
```

### 3. 更新User模型

- 添加`username`到`$fillable`数组
- 修改`getAuthIdentifierName()`方法返回`'username'`
- 修改`getUsername()`方法返回`$this->username`
- 删除不再需要的兼容性方法

### 4. 测试结果

✅ 用户后台登录功能正常工作
✅ 使用email作为username可以成功登录
✅ 仪表板正常显示用户信息和统计数据
✅ 左侧菜单正常显示

## 技术细节

### 数据库变更
- `users`表新增`username`字段（NOT NULL, UNIQUE）
- `user_admin_menu`表新增`show`和`extension`字段
- 现有用户的username字段自动设置为email值

### 认证流程
- dcat-admin现在使用`username`字段进行认证
- 用户可以使用email地址作为用户名登录
- 保持了向后兼容性

## 验证步骤

1. 访问 http://127.0.0.1:34004/user-admin
2. 使用 `test@example.com` / `password` 登录
3. 确认登录成功并显示仪表板
4. 检查菜单功能正常

## 文件修改

- `laravel/database/migrations/2025_07_20_132130_add_username_to_users_table.php` (新建)
- `laravel/database/migrations/2025_07_20_131817_add_missing_fields_to_user_admin_menu_table.php` (新建)
- `laravel/app/Modules/User/Models/User.php` (修改)

## 后续修复

### 5. 修复admin后台登录问题

**问题原因**: DatabaseSeeder中缺少AdminTablesSeeder调用，导致admin用户没有被创建。

**解决方案**:
```php
// 在DatabaseSeeder.php中添加
public function run(): void
{
    // Seed admin tables first
    $this->call(AdminTablesSeeder::class);

    // ... 其他seeder
}
```

AdminTablesSeeder包含：
- 创建默认admin用户（用户名：admin，密码：admin）
- 创建管理员角色和权限
- 创建admin后台菜单结构

### 6. 最终验证

✅ 超级管理员后台 (/admin) - 用户名：admin，密码：admin
✅ 用户后台 (/user-admin) - 用户名：test@example.com，密码：password

## 状态

✅ **已完成** - 用户表username字段修复完成，两个后台系统都正常工作
