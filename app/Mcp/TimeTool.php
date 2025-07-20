<?php
namespace App\Mcp;

use PhpMcp\Server\Attributes\{McpTool, McpResource, McpResourceTemplate, McpPrompt};


class TimeTool
{

    /**
     * Get application configuration.
     */
    #[McpResource(
        uri: 'time://get',
        mimeType: 'application/json'
    )]
    public function getAppSettings(): array
    {
        return [
            'date' => date('Y-m-d h:i:s'),
        ];
    }

}