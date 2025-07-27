<?php

use Modules\MCP\Tools\TimeTool;
use Modules\MCP\Tools\Time2Tool;
use PhpMCP\Laravel\Facades\MCP;

// Register a resource with metadata
// MCP::resource('time://get', [Modules\MCP\Tools\TimeTool::class, 'get_time'])
//     ->name('app_settings')
//     ->mimeType('application/json');

// Register Time2Tool resource manually
// MCP::resource('time://get2', [Time2Tool::class, 'getTime2'])
//     ->name('getTime2')
//     ->mimeType('application/json');