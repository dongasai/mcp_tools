# 技术文档

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

### 包的作用和集成

#### 1. php-mcp/laravel
- **作用**：提供MCP协议的Laravel原生支持
- **功能**：MCP服务器、资源管理、工具注册
- **集成位置**：MCP协议模块的核心实现

#### 2. dcat/laravel-admin
- **作用**：快速构建后台管理界面
- **功能**：用户管理、项目管理、任务监控、Agent管理
- **集成位置**：Admin管理模块

#### 3. inhere/php-validate
- **作用**：强大的数据验证库
- **功能**：API请求验证、MCP消息验证、业务数据验证
- **集成位置**：所有模块的数据验证层

#### 4. spatie/laravel-route-attributes
- **作用**：使用PHP属性定义路由
- **功能**：简化路由定义、提高代码可读性
- **集成位置**：所有模块的控制器路由定义

## 开发规范

### 1. 模块化开发
- 每个模块独立的目录结构
- 模块列表：
    - app/UserAdmin/ 用户后台模块，使用dcatadmin构建
    - app/Admin/ 超级管理员后台模块，使用dcatadmin构建
    - app/Modules/Mcp Mcp模块

### 2. 代码规范
- PSR-12 代码风格
- 类型声明和返回类型
- 完整的PHPDoc注释

### 3. 架构原则
- 单一职责原则
- 依赖注入
- 事件驱动架构

