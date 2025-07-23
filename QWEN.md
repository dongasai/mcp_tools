# Qwen Code 项目定制文档 (MCP Tools)

## 项目概述
这是一个基于 Laravel 的 PHP 项目，专注于实现 MCP (Model Context Protocol) 工具系统。项目包含：
- 使用 dcat-admin 的管理面板
- 用户管理系统
- 任务和项目管理模块
- 基于 Agent 的交互系统
- 使用 SQLite 的数据库管理

## 技术栈与约定
- **后端**: PHP/Laravel
- **前端**: Blade 模板，可能使用 Bootstrap/Material Design
- **数据库**: 主要使用 SQLite，也可能使用其他数据库
- **管理面板**: dcat-admin
- **架构**: 模块化设计，包含 Core、Agent 等模块
- **测试**: PHPUnit，使用 Dusk 进行浏览器测试

## 代码风格偏好
- 遵循 PSR-12 PHP 编码标准
- 使用清晰、描述性的变量和函数名
- 为类和方法添加文档注释
- 保持一致的缩进（PHP 使用 4 个空格）

## 工作流程指南
1. 在提出代码更改时，首先检查代码库中的现有模式
2. 在处理管理界面时，优先使用 Laravel 约定和 dcat-admin 模式
3. 在建议使用依赖项之前，先验证其是否存在
4. 修改数据库结构时，要考虑迁移文件及其顺序
5. 创建新功能时，查找现有的类似实现以保持一致性

## 沟通偏好
- 直接简洁
- 注重技术准确性
- 对复杂的逻辑或架构决策提供解释
- 展示代码示例时，要匹配项目现有的风格
- 在涉及项目特定术语或更适合中文表达的上下文时使用中文

## 常见目录和文件
- `/app/Models/` - 全局 Eloquent 模型（仅包含 AuthUser.php 用于标准 Laravel 认证）
- `/app/Modules/` - 模块化组件（各模块的扩展模型位于其 Models 子目录中，包含完整的业务逻辑）
- `/app/Admin/` - dcat-admin 配置
- `/config/` - 配置文件
- `/database/migrations/` - 数据库模式更改
- `/resources/views/` - Blade 模板
- `/routes/` - 路由定义
- `/tests/` - 测试文件

## 项目特定术语
- **MCP**: Model Context Protocol - 一种用于 LLM 交互的协议
- **Agent**: 处理 LLM 交互的组件
- **UserAdmin**: 用于用户管理的自定义管理系统
- **Task/Project**: 核心领域模型
- **dcat-admin**: 正在使用的管理面板系统