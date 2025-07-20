<?php
namespace App\Mcp;

use PhpMcp\Server\Attributes\{McpTool, McpResource, McpResourceTemplate, McpPrompt};


class TimeTool
{

    /**
     * Get current time information including formatted time, timestamp and timezone
     */
    #[McpResource(
        uri: 'time://get',
        mimeType: 'application/json'
    )]
    public function time_get(): array
    {
        $now = new \DateTime();
        
        return [
            'formatted_time' => $now->format('Y-m-d H:i:s'),
            'timestamp' => $now->getTimestamp(),
            'timezone' => $now->getTimezone()->getName(),
            'utc_time' => $now->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s'),
            'microseconds' => $now->format('Y-m-d H:i:s.u'),
        ];
    }

}