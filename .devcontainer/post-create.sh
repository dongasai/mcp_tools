#!/bin/bash

echo "🚀 正在初始化 MCP Tools 开发环境..."

# 安装 Composer 依赖
if [ -f composer.json ]; then
    echo "📦 安装 Composer 依赖..."
    composer install --no-interaction --optimize-autoloader
fi

# 使用 composer 命令初始化项目
echo "⚙️  执行项目初始化..."
composer run project-init

echo "✅ 开发环境初始化完成！"
echo "🌐 访问地址: http://localhost:34004"