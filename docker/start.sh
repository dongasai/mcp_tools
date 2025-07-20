#!/bin/bash

# 确保存储目录存在并设置正确权限
mkdir -p /var/www/html/storage/logs \
         /var/www/html/storage/app/public \
         /var/www/html/storage/framework/cache \
         /var/www/html/storage/framework/sessions \
         /var/www/html/storage/framework/views \
         /var/www/html/bootstrap/cache

# 设置正确的所有权和权限
chown -R appuser:appuser /var/www/html 2>/dev/null || true
chmod -R 755 /var/www/html
chmod -R 777 /var/www/html/storage /var/www/html/bootstrap/cache

# 启动supervisor
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/laravel.conf