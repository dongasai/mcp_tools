# 开发文档

## 模块规范

- **类型**: laravel-module
- **表前缀**: dcatadmin2demo_
- **命名空间**: DcatAdminDemo
- **模型位置**: src/Models/

## 文件结构

```
dcatadmin_demo-module/
├── composer.json
├── module.json
└── src/
```

## src 目录详细说明

### src/Models/
**用途**: 存放 Eloquent 模型类
- **Demo.php** - 演示数据模型，对应表 `dcatadmin2demo_demos`
- 命名空间: `DcatAdminDemo\Models`

### src/database/
**用途**: 数据库相关文件
- **migrations/** - 迁移文件，定义数据库表结构
  - `2025_07_27_000000_create_demos_table.php` - 创建演示表
- **seeders/** - 种子文件，填充测试数据
- **factories/** - 模型工厂，生成测试数据

### src/Http/
**用途**: HTTP 层相关文件
- **Controllers/** - 控制器类
  - `AdminDemoController.php` - 演示模块后台控制器
  - `BaseController.php` - 基础控制器
- **Middleware/** - 中间件类
- **Requests/** - 表单请求验证类

### src/resources/
**用途**: 资源文件
- **views/** - 视图模板文件
  - `index.blade.php` - 列表页面
  - `show.blade.php` - 详情页面
  - **layouts/** - 布局文件
    - `master.blade.php` - 主布局模板
- **assets/** - 静态资源文件（CSS、JS、图片）

### src/config/
**用途**: 配置文件
- **config.php** - 基础配置文件
- **madmindemo.php** - 模块特定配置

### src/routes/
**用途**: 路由定义文件
- **admin.php** - 后台管理路由
- **api.php** - API 接口路由
- **web.php** - 前端路由

### src/Providers/
**用途**: 服务提供者
- **MAdminDemoServiceProvider.php** - 模块服务提供者，注册模块服务

### src/Console/
**用途**: 自定义 Artisan 命令
- 存放模块特定的命令行工具

### src/lang/
**用途**: 语言文件
- 多语言支持，按语言代码分目录

### src/Casts/
**用途**: 自定义属性转换类
- 扩展 Eloquent 的属性类型转换功能

### src/Emails/
**用途**: 邮件类
- 模块特定的邮件模板和逻辑

### src/Events/
**用途**: 事件类
- 定义模块触发的事件

### src/Listeners/
**用途**: 事件监听器
- 处理模块事件的响应逻辑

### src/Jobs/
**用途**: 队列任务类
- 异步处理的任务逻辑

### src/Notifications/
**用途**: 通知类
- 系统通知、邮件通知等

### src/Policies/
**用途**: 授权策略类
- 定义模型权限控制逻辑

### src/Repositories/
**用途**: 数据仓库类
- 封装数据库查询逻辑

### src/Rules/
**用途**: 自定义验证规则
- 扩展 Laravel 的验证规则

### src/Transformers/
**用途**: 数据转换器
- API 数据格式转换（用于 Fractal 等）

### src/View/
**用途**: 视图组件
- **Components/** - Blade 组件类
- 自定义视图组件和指令

### src/tests/
**用途**: 测试文件
- **Feature/** - 功能测试
- **Unit/** - 单元测试

## 数据库规范

- 表名: `dcatadmin2demo_demos`
- 字段: id, name, description, status, created_at, updated_at, deleted_at
- 模型: `DcatAdminDemo\Models\Demo`

## 开发命令

```bash
# 安装
composer require dcatadmin2/dcatadmin_demo-module

# 启用模块
php artisan module:enable dcatadmindemo

# 运行迁移
php artisan module:migrate dcatadmindemo

# 运行种子
php artisan module:seed dcatadmindemo