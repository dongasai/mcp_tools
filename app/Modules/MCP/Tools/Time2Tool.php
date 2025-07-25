<?php

namespace App\Modules\MCP\Tools;

use PhpMCP\Server\Attributes\{MCPResource};
use App\Modules\Agent\Services\AuthenticationService;
use App\Modules\User\Models\User;
use Carbon\Carbon;

class Time2Tool
{
    public function __construct(
        private AuthenticationService $authService
    ) {}

    /**
     * Get current time with user timezone information.
     */
    #[MCPResource(
        uri: 'time://current',
        name: 'time_current',
        mimeType: 'application/json'
    )]
    public function getTime2(): array
    {
        try {
            // 获取当前Agent和用户信息
            $agent = $this->getCurrentAgent();
            $user = User::find($agent->user_id);

            // 获取用户时区，如果没有设置则使用系统默认时区
            $userTimezone = $user?->timezone ?: config('app.timezone', 'UTC');

            // 创建当前时间的Carbon实例
            $utcTime = Carbon::now('UTC');
            $userTime = Carbon::now($userTimezone);
            $systemTime = Carbon::now(config('app.timezone', 'UTC'));

            return [
                'utc' => [
                    'date' => $utcTime->format('Y-m-d H:i:s'),
                    'timestamp' => $utcTime->timestamp,
                    'iso8601' => $utcTime->toISOString(),
                ],
                'user' => [
                    'date' => $userTime->format('Y-m-d H:i:s'),
                    'timezone' => $userTimezone,
                    'offset' => $userTime->format('P'),
                    'iso8601' => $userTime->toISOString(),
                ],
                'system' => [
                    'date' => $systemTime->format('Y-m-d H:i:s'),
                    'timezone' => config('app.timezone', 'UTC'),
                    'offset' => $systemTime->format('P'),
                ],
            ];
        } catch (\Exception $e) {
            // 如果无法获取用户信息，返回基本时间信息
            $utcTime = Carbon::now('UTC');
            $systemTime = Carbon::now(config('app.timezone', 'UTC'));
            return [
                'utc' => [
                    'date' => $utcTime->format('Y-m-d H:i:s'),
                    'timestamp' => $utcTime->timestamp,
                    'iso8601' => $utcTime->toISOString(),
                ],
                'system' => [
                    'date' => $systemTime->format('Y-m-d H:i:s'),
                    'timezone' => config('app.timezone', 'UTC'),
                    'offset' => $systemTime->format('P'),
                ],
                'error' => 'Unable to get user timezone: ' . $e->getMessage()
            ];
        }
    }

    /**
     * 获取当前认证的Agent
     */
    private function getCurrentAgent(): \App\Modules\Agent\Models\Agent
    {
        $authInfo = $this->authService->extractAuthFromRequest(request());

        if (!$authInfo['token']) {
            throw new \Exception('No authentication token provided');
        }

        // 优先使用完整认证，如果没有agent_id则使用token-only认证
        if ($authInfo['agent_id']) {
            $agent = $this->authService->authenticate($authInfo['token'], $authInfo['agent_id']);
        } else {
            $agent = $this->authService->authenticateByTokenOnly($authInfo['token']);
        }

        if (!$agent) {
            throw new \Exception('Invalid authentication token or agent ID');
        }

        return $agent;
    }
}