# 技术文档

> 这是一个基于 Laravel 的 Model Context Protocol (MCP) 服务器

## 技术选型

### 核心框架
1. **Laravel 11** - 主框架
2. **SQLite** - 数据库（开发阶段）
2. **Mysql8.0+** - 数据库（开发阶段）

### 核心包依赖
1. **php-mcp/laravel** - MCP协议Laravel集成包
2. **dcat/laravel-admin** - 后台管理界面
3. **inhere/php-validate** - 数据验证库
4. **laravel/tinker** - 命令行交互工具
5. **spatie/laravel-route-attributes** - 路由属性注解
6. **nwidart/laravel-modules** - Laravel模块化开发框架

### 包的作用和集成

#### 1. php-mcp/laravel
- **作用**：提供MCP协议的Laravel原生支持
- **功能**：MCP服务器、资源管理、工具注册，为MCP提供底层支持

#### 2. dcat/laravel-admin
- **作用**：快速构建后台管理界面
- **功能**：用户管理、项目管理、任务监控、Agent管理
- **集成位置**：Admin管理模块

#### 3. inhere/php-validate
- **作用**：强大的数据验证库
- **功能**：API请求验证、MCP消息验证、业务数据验证
- **集成位置**：所有模块的数据验证层

#### 4. nwidart/laravel-modules
- **作用**：Laravel模块化开发框架
- **功能**：提供完整的模块化架构支持，包括模块的创建、管理、路由、配置、迁移等
- **集成位置**：整个项目的核心架构，所有业务模块都基于此框架构建
- **特点**：
  - 支持模块独立的路由、控制器、模型、视图
  - 模块间松耦合，可独立启用/禁用
  - 支持模块自动加载和依赖管理
  - 提供模块生成器命令行工具



## 开发规范

### 1. 模块化开发
基于 **nwidart/laravel-modules** 框架的完整模块化架构：

#### 模块目录结构
- **Modules/** - 所有业务模块的根目录
- **app/** - 核心应用代码（非模块化部分）

#### 现有模块列表
- **Modules/User/** - 用户管理模块
- **Modules/Project/** - 项目管理模块
- **Modules/Task/** - 任务管理模块
- **Modules/MCP/** - MCP协议实现模块，MCP底层由`php-mcp/laravel`提供
- **Modules/Test/** - 测试模块
- **Modules/Dbcont/** - 数据库内容管理模块
- **Modules/DcatAdminDemo/** - DcatAdmin演示模块
- **Modules/AdminDemo/** - 管理员演示模块

#### 模块标准结构
每个模块遵循统一的标准结构：
```
Modules/{ModuleName}/
├── config/           # 模块配置
├── Console/          # 命令行命令
├── database/         # 数据库迁移和填充
├── Enums/            # 枚举定义
├── Events/           # 事件定义
├── Http/             # 控制器、中间件、请求
├── Models/           # 数据模型
├── Providers/        # 服务提供者
├── resources/        # 视图、语言文件、资源
├── routes/           # 路由定义
├── Services/         # 业务逻辑服务
└── Tests/            # 模块测试
```

#### 模块管理命令
- `php artisan module:list` - 列出所有模块
- `php artisan module:make {ModuleName}` - 创建新模块
- `php artisan module:enable {ModuleName}` - 启用模块
- `php artisan module:disable {ModuleName}` - 禁用模块
- `php artisan module:migrate {ModuleName}` - 运行模块迁移

### 2. 代码规范
- PSR-12 代码风格
- 类型声明和返回类型
- 完整的PHPDoc注释

### 3. 架构原则
- 单一职责原则
- 依赖注入
- 事件驱动架构

