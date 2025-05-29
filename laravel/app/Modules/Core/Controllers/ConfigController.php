<?php

namespace App\Modules\Core\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Modules\Core\Contracts\ConfigInterface;
use App\Modules\Core\Contracts\LogInterface;

class ConfigController extends Controller
{
    protected ConfigInterface $config;
    protected LogInterface $logger;

    public function __construct(ConfigInterface $config, LogInterface $logger)
    {
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * 获取所有配置
     */
    public function index(): JsonResponse
    {
        try {
            $configs = $this->config->all();
            
            // 过滤敏感信息
            $filteredConfigs = $this->filterSensitiveData($configs);
            
            return response()->json([
                'success' => true,
                'data' => $filteredConfigs,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to get configurations', [
                'error' => $e->getMessage(),
                'user' => auth()->user()?->id,
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve configurations',
            ], 500);
        }
    }

    /**
     * 获取单个配置
     */
    public function show(string $key): JsonResponse
    {
        try {
            if (!$this->config->has($key)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Configuration key not found',
                ], 404);
            }

            $value = $this->config->get($key);
            
            // 检查是否为敏感配置
            if ($this->isSensitiveKey($key)) {
                $value = '***HIDDEN***';
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'key' => $key,
                    'value' => $value,
                ],
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to get configuration', [
                'key' => $key,
                'error' => $e->getMessage(),
                'user' => auth()->user()?->id,
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve configuration',
            ], 500);
        }
    }

    /**
     * 创建配置
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $key = $request->input('key');
            $value = $request->input('value');

            if (empty($key)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Configuration key is required',
                ], 400);
            }

            $this->config->set($key, $value);
            
            $this->logger->audit('config_created', auth()->user()?->id ?? 'system', [
                'key' => $key,
                'value_type' => gettype($value),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Configuration created successfully',
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to create configuration', [
                'key' => $request->input('key'),
                'error' => $e->getMessage(),
                'user' => auth()->user()?->id,
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to create configuration',
            ], 500);
        }
    }

    /**
     * 更新配置
     */
    public function update(Request $request, string $key): JsonResponse
    {
        try {
            $value = $request->input('value');
            $oldValue = $this->config->get($key);

            $this->config->set($key, $value);
            
            $this->logger->audit('config_updated', auth()->user()?->id ?? 'system', [
                'key' => $key,
                'old_value_type' => gettype($oldValue),
                'new_value_type' => gettype($value),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Configuration updated successfully',
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to update configuration', [
                'key' => $key,
                'error' => $e->getMessage(),
                'user' => auth()->user()?->id,
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to update configuration',
            ], 500);
        }
    }

    /**
     * 删除配置
     */
    public function destroy(string $key): JsonResponse
    {
        try {
            if (!$this->config->has($key)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Configuration key not found',
                ], 404);
            }

            $this->config->set($key, null);
            
            $this->logger->audit('config_deleted', auth()->user()?->id ?? 'system', [
                'key' => $key,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Configuration deleted successfully',
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to delete configuration', [
                'key' => $key,
                'error' => $e->getMessage(),
                'user' => auth()->user()?->id,
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to delete configuration',
            ], 500);
        }
    }

    /**
     * 刷新配置缓存
     */
    public function refresh(): JsonResponse
    {
        try {
            $this->config->refresh();
            
            $this->logger->audit('config_refreshed', auth()->user()?->id ?? 'system');

            return response()->json([
                'success' => true,
                'message' => 'Configuration cache refreshed successfully',
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to refresh configuration cache', [
                'error' => $e->getMessage(),
                'user' => auth()->user()?->id,
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to refresh configuration cache',
            ], 500);
        }
    }

    /**
     * 过滤敏感数据
     */
    protected function filterSensitiveData(array $configs): array
    {
        $sensitiveKeys = [
            'app.key',
            'database.connections',
            'mail.password',
            'services.github.client_secret',
            'jwt.secret',
        ];

        foreach ($sensitiveKeys as $sensitiveKey) {
            if (isset($configs[$sensitiveKey])) {
                $configs[$sensitiveKey] = '***HIDDEN***';
            }
        }

        return $configs;
    }

    /**
     * 检查是否为敏感配置键
     */
    protected function isSensitiveKey(string $key): bool
    {
        $sensitivePatterns = [
            'password',
            'secret',
            'key',
            'token',
            'api_key',
            'private',
        ];

        $lowerKey = strtolower($key);
        
        foreach ($sensitivePatterns as $pattern) {
            if (str_contains($lowerKey, $pattern)) {
                return true;
            }
        }

        return false;
    }
}
