<?php

namespace App\Modules\Mcp\Tools;



use PhpMcp\Server\Attributes\{McpTool, McpResource, McpResourceTemplate, McpPrompt};

class Time2Tool
{

    /**
     * Get application configuration.
     */
    #[McpResource(
        uri: 'time://get2',
        mimeType: 'application/json'
    )]
    public function gettime2(): array
    {
        return [
            'date' => date('Y-m-d h:i:s'),
        ];
    }

}