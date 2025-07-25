<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| This project does not expose traditional REST APIs.
| All functionality is accessed through:
| 1. MCP protocol for LLM interactions
| 2. Admin panel for super admin functions
| 3. UserAdmin panel for user functions
|
*/

// 包含Task模块测试路由
require_once __DIR__ . '/../app/Modules/Task/routes/test.php';
