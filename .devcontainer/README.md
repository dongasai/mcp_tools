# MCP Tools 开发容器使用指南

## 什么是开发容器？
开发容器让你在 Docker 容器中开发，确保所有团队成员使用完全一致的开发环境。

## 快速开始

### 1. 安装要求
- **Docker Desktop**（[下载地址](https://www.docker.com/products/docker-desktop/)）
- **VS Code**（[下载地址](https://code.visualstudio.com/)）
- **Remote-Containers 扩展**（VS Code 中搜索安装）

### 2. 启动开发环境

#### 方法一：自动提示（推荐）
1. 用 VS Code 打开项目
2. 右下角会弹出提示："在容器中重新打开"
3. 点击即可自动构建和启动

#### 方法二：手动启动
1. 按 `F1` 打开命令面板
2. 输入并选择："Dev Containers: Reopen in Container"
3. 等待构建完成（首次约 2-5 分钟）

### 3. 开始使用
构建完成后，你会看到：
- ✅ 终端显示 "开发环境初始化完成！"
- 🌐 浏览器自动打开 http://localhost:34004

## 常用操作

| 操作 | 命令/方法 |
|------|-----------|
| 重新构建容器 | F1 → "Dev Containers: Rebuild Container" |
| 查看容器日志 | F1 → "Dev Containers: Show Log" |
| 在主机和容器间切换 | F1 → "Dev Containers: Reopen Locally" |
| 安装新扩展 | 直接在 VS Code 扩展面板安装 |

## 目录结构
```
.devcontainer/
├── devcontainer.json          # VS Code 配置
├── docker-compose.devcontainer.yml  # Docker 服务
├── post-create.sh            # 初始化脚本
└── README.md                # 本使用说明
```

## 技术细节
- **PHP 版本**：8.2（来自 Dockerfile.dev）
- **Web 服务器**：Apache
- **端口**：34004
- **工作目录**：/workspace

## 故障排除
- **构建失败**：检查 Docker Desktop 是否运行
- **端口占用**：修改 `docker-compose.devcontainer.yml` 中的端口
- **权限问题**：重启 VS Code 或重新构建容器

## 与传统方式对比
| 传统方式 | 开发容器方式 |
|----------|--------------|
| 手动安装 PHP/Apache | 一键启动完整环境 |
| 环境可能不一致 | 100% 环境一致性 |
| 新成员配置耗时 | 新成员几分钟上手 |
| 系统冲突风险 | 完全隔离无冲突 |