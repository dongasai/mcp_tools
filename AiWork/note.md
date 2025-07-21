# 笔记


npx -y @modelcontextprotocol/inspector

modelcontextprotocol测试工具已经运行，网址 http://localhost:6274/ 
对mcp进行测试 

https://packagist.org/packages/php-mcp/laravel


发现命令
从代码库中发现并缓存 MCP 元素：

# Discover elements and update cache
php artisan mcp:discover

# Force re-discovery (ignore existing cache)
php artisan mcp:discover --force

# Discover without updating cache
php artisan mcp:discover --no-cache

# List all elements
php artisan mcp:list

# List specific type
php artisan mcp:list tools
php artisan mcp:list resources
php artisan mcp:list prompts
php artisan mcp:list templates

# JSON output
php artisan mcp:list --json

https://context7.com/zavierd/dcat-admin-doc/llms.txt?topic=%E8%A1%8C%E6%93%8D%E4%BD%9C
