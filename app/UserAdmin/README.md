# UserAdmin 用户后台模块

## 概述

UserAdmin用户后台模块为普通用户提供个人管理界面，用户可以通过该界面管理自己的项目、任务、Agent和个人资源。该模块基于dcat/laravel-admin构建，提供直观友好的用户体验。

## 职责范围

### 1. 个人项目管理
- 创建和管理个人项目
- 项目成员邀请和管理
- 项目设置和配置
- 项目统计和分析

### 2. 任务管理
- 查看和管理项目任务
- 任务进度跟踪
- 子任务监控
- 任务统计分析

### 3. Agent管理
- 注册和配置个人Agent
- Agent权限设置
- Agent性能监控
- Agent使用统计

### 4. 个人资源管理
- GitHub账户连接
- API密钥管理
- 个人设置配置
- 通知偏好设置

### 5. 数据分析
- 个人工作统计
- 项目进度分析
- Agent效率分析
- 使用趋势报告

## 目录结构

```
app/Modules/UserAdmin/
├── Controllers/
│   ├── DashboardController.php     # 用户仪表板
│   ├── ProjectController.php       # 项目管理
│   ├── TaskController.php          # 任务管理
│   ├── AgentController.php         # Agent管理
│   ├── GitHubController.php        # GitHub集成
│   ├── ProfileController.php       # 个人资料
│   └── SettingsController.php      # 个人设置
├── Models/
│   ├── UserProject.php             # 用户项目关联
│   ├── UserAgent.php               # 用户Agent关联
│   ├── UserGitHubConnection.php    # GitHub连接
│   └── UserPreference.php          # 用户偏好
├── Services/
│   ├── UserAdminService.php        # 用户后台服务
│   ├── ProjectManagementService.php # 项目管理服务
│   ├── AgentManagementService.php  # Agent管理服务
│   └── GitHubIntegrationService.php # GitHub集成服务
├── Widgets/
│   ├── ProjectStatsWidget.php      # 项目统计组件
│   ├── TaskProgressWidget.php      # 任务进度组件
│   ├── AgentStatusWidget.php       # Agent状态组件
│   └── ActivityTimelineWidget.php  # 活动时间线组件
├── Actions/
│   ├── ConnectGitHubAction.php     # 连接GitHub操作
│   ├── CreateProjectAction.php     # 创建项目操作
│   ├── RegisterAgentAction.php     # 注册Agent操作
│   └── ExportDataAction.php        # 导出数据操作
├── Middleware/
│   ├── UserAdminAuth.php           # 用户后台认证
│   └── ProjectOwnership.php        # 项目所有权验证
├── Providers/
│   └── UserAdminServiceProvider.php # 服务提供者
└── config/
    └── user-admin.php              # 用户后台配置
```

## 核心控制器

### 1. DashboardController - 用户仪表板
提供用户工作台界面，展示以下核心组件：
- 快速统计面板：显示项目数、活跃任务、完成任务数和Agent数量
- 项目概览：展示用户所有项目的基本信息
- 任务进度图表：以环形图展示任务状态分布
- Agent状态面板：显示用户所有Agent的当前状态
- 最近活动时间线：展示用户近期操作记录

### 2. ProjectController - 项目管理
提供项目全生命周期管理功能：
- 项目列表展示：表格形式显示项目名称、状态、任务数量等关键信息
- 项目创建表单：包含名称、描述、状态、时区等必填字段
- 项目设置：支持自动分配任务、GitHub同步等高级配置
- 项目详情页：展示任务列表、关联仓库等详细信息
- 成员管理：支持项目成员添加和权限分配

### 3. AgentController - Agent管理
提供Agent注册和管理功能：
- Agent列表：展示Agent ID、名称、类型、状态等关键信息
- Agent注册表单：包含名称、类型、描述、授权项目等配置项
- 权限管理：设置Agent可执行的操作（读取数据、创建任务等）
- 令牌管理：支持生成和重置访问令牌
- 执行记录：查看Agent完成的子任务历史

### 4. GitHubController - GitHub集成
提供GitHub账户连接和仓库管理：
- OAuth认证流程：实现GitHub账户安全连接
- 仓库列表展示：显示用户GitHub仓库的基本信息
- 连接状态面板：展示当前账户连接状态
- 同步历史记录：查看仓库同步操作日志

## 配置文件

### user-admin.php
用户后台模块的配置文件，包含：
- 路由前缀和中间件配置
- 认证守卫和用户提供者设置
- 系统限制参数（最大项目数、Agent数等）
- 功能开关（项目创建、Agent注册等权限控制）
- GitHub集成配置（OAuth作用域和Webhook事件）

---

**相关文档**：
- [超级管理员模块](./Admin.md)
- [项目模块](/app/Modules/Project/README.md)
- [任务模块](/app/Modules/Task/README.md)
- [Agent模块](/app/Modules/Agent/README.md)
