<?php

namespace App\Modules\Mcp\Tools;

use PhpMcp\Server\Attributes\{McpResource};

class Time2Tool
{
    /**
     * Get current time.
     */
    #[McpResource(
        uri: 'time://get2',
        mimeType: 'application/json'
    )]
    public function getTime2(): array
    {
        return [
            'date' => date('Y-m-d H:i:s'),
            'timestamp' => time(),
        ];
    }
}