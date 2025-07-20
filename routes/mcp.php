<?php

use App\Modules\Mcp\Tools\TimeTool;
use App\Modules\Mcp\Tools\Time2Tool;
use PhpMcp\Laravel\Facades\Mcp;

// Register a resource with metadata
// Mcp::resource('time://get', [App\Modules\Mcp\Tools\TimeTool::class, 'get_time'])
//     ->name('app_settings')
//     ->mimeType('application/json');

// Register Time2Tool resource manually
// Mcp::resource('time://get2', [Time2Tool::class, 'getTime2'])
//     ->name('getTime2')
//     ->mimeType('application/json');