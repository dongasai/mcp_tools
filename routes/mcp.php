<?php

use PhpMcp\Laravel\Facades\Mcp;

/*
|--------------------------------------------------------------------------
| MCP Tools Registration
|--------------------------------------------------------------------------
|
| Register MCP tools that can be called by AI agents
|
| 注意：大部分工具使用属性(Attributes)自动发现，这里只注册一些简单的示例工具
|
*/

// 注意：当前版本的php-mcp/laravel不支持闭包工具
// 工具将通过属性(Attributes)自动发现，或者需要创建专门的工具类

// 示例：如果需要手动注册工具，需要使用类方法
// Mcp::tool('tool_name', [ClassName::class, 'methodName'])
//     ->description('Tool description');

/*
|--------------------------------------------------------------------------
| MCP Resources Registration
|--------------------------------------------------------------------------
|
| Register MCP resources that provide read-only access to data
|
*/

// 注意：当前版本的php-mcp/laravel不支持闭包资源
// 资源将通过属性(Attributes)自动发现，或者需要创建专门的资源类

// 示例：如果需要手动注册资源，需要使用类方法
// Mcp::resource('resource://uri', [ClassName::class, 'methodName'])
//     ->name('resource_name')
//     ->description('Resource description')
//     ->mimeType('application/json');
