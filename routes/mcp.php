<?php

use App\Modules\Mcp\Tools\TimeTool;
use PhpMcp\Laravel\Facades\Mcp;
use App\Services\{CalculatorService, UserService, EmailService, PromptService};

// Register a resource with metadata
// Mcp::resource('time://get', [App\Modules\Mcp\Tools\TimeTool::class, 'get_time'])
//     ->name('app_settings')
//     ->mimeType('application/json');