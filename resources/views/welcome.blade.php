<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Laravel</title>

        <!-- Fonts -->
        <!-- 使用系统字体，不依赖外部服务 -->

        <!-- Styles -->
        <style>
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
                margin: 0;
                padding: 2rem;
                background-color: #f3f4f6;
                color: #1f2937;
            }
            .container {
                max-width: 1200px;
                margin: 0 auto;
                text-align: center;
            }
            h1 {
                color: #111827;
                margin-bottom: 1rem;
            }
            .links {
                margin-top: 2rem;
            }
            .links a {
                color: #3b82f6;
                text-decoration: none;
                margin: 0 1rem;
            }
            .links a:hover {
                text-decoration: underline;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>MCP Tools Server</h1>
            <p>基于Laravel的Model Context Protocol服务器</p>
            
            <div class="links">
                <a href="/admin">超级管理员后台</a>
                <a href="/user-admin">用户后台</a>
                <a href="/api/mcp">MCP API</a>
            </div>
        </div>
    </body>
</html>