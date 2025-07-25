<?php

namespace App\Modules\Core\Services;

use App\Modules\Core\Contracts\ValidationInterface;
use Inhere\Validate\Validation;

class ValidationService implements ValidationInterface
{
    protected array $errors = [];
    protected array $customRules = [];
    protected array $customMessages = [];

    public function __construct()
    {
        $this->customMessages = config('core.validation.custom_messages.zh-CN', []);
        $this->registerCustomRules();
    }

    /**
     * 验证数据
     */
    public function validate(array $data, array $rules, array $messages = []): array
    {
        $this->errors = [];

        // 转换规则格式为 inhere/validate 格式
        $convertedRules = $this->convertRulesToInhereFormat($rules);

        $validation = Validation::make($data, $convertedRules, $messages);
        $validation->validate();

        if ($validation->isOk()) {
            return $validation->getSafeData();
        }

        $this->errors = $validation->getErrors();
        return [];
    }

    /**
     * 验证单个字段
     */
    public function validateField(string $field, mixed $value, string|array $rules): bool
    {
        $data = [$field => $value];
        $ruleSet = [$field => $rules];

        $result = $this->validate($data, $ruleSet);
        return !empty($result);
    }

    /**
     * 获取验证错误
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * 检查是否有错误
     */
    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    /**
     * 添加自定义验证规则
     */
    public function addRule(string $name, callable $callback): void
    {
        $this->customRules[$name] = $callback;

        // 注册到 inhere/validate 的全局验证器
        // \Inhere\Validate\Validator\UserValidators::add($name, $callback);
        // 暂时注释掉，因为该方法不存在
    }

    /**
     * 设置错误消息
     */
    public function setMessages(array $messages): void
    {
        $this->customMessages = array_merge($this->customMessages, $messages);
    }

    /**
     * 验证MCP消息格式
     */
    public function validateMCPMessage(array $message): array
    {
        $rules = [
            'jsonrpc' => 'required|string|in:2.0',
            'method' => 'required|string',
            'id' => 'integer|string',
        ];

        // 根据方法类型添加特定规则
        if (isset($message['method'])) {
            switch ($message['method']) {
                case 'initialize':
                    $rules['params.protocolVersion'] = 'required|string';
                    $rules['params.capabilities'] = 'required|array';
                    $rules['params.clientInfo.name'] = 'required|string';
                    $rules['params.clientInfo.version'] = 'required|string';
                    break;

                case 'resources/read':
                    $rules['params.uri'] = 'required|string|mcp_uri';
                    break;

                case 'tools/call':
                    $rules['params.name'] = 'required|string';
                    $rules['params.arguments'] = 'required|array';
                    break;
            }
        }

        return $this->validate($message, $rules);
    }

    /**
     * 验证Agent权限
     */
    public function validateAgentPermissions(string $agentId, array $permissions): bool
    {
        $rules = [
            'agentId' => 'required|string|min:1|agent_id',
            'permissions' => 'required|array|min:1',
        ];

        $data = [
            'agentId' => $agentId,
            'permissions' => $permissions,
        ];

        $result = $this->validate($data, $rules);
        return !empty($result);
    }

    /**
     * 验证项目访问权限
     */
    public function validateProjectAccess(string $agentId, int $projectId): bool
    {
        $rules = [
            'agentId' => 'required|string|min:1|agent_id',
            'projectId' => 'required|integer|min:1',
        ];

        $data = [
            'agentId' => $agentId,
            'projectId' => $projectId,
        ];

        $result = $this->validate($data, $rules);
        return !empty($result);
    }

    /**
     * 批量验证
     */
    public function validateBatch(array $items, array $rules): array
    {
        $results = [];
        $allErrors = [];

        foreach ($items as $index => $item) {
            $result = $this->validate($item, $rules);

            if (!empty($result)) {
                $results[$index] = $result;
            } else {
                $allErrors[$index] = $this->getErrors();
            }
        }

        $this->errors = $allErrors;
        return $results;
    }

    /**
     * 注册自定义验证规则
     */
    protected function registerCustomRules(): void
    {
        // MCP URI 验证规则
        $this->addRule('mcp_uri', function ($value, $data = null) {
            if (!is_string($value)) {
                return false;
            }

            // 验证MCP URI格式: scheme://path
            $pattern = '/^[a-zA-Z][a-zA-Z0-9+.-]*:\/\/.+$/';
            return preg_match($pattern, $value) === 1;
        });

        // Agent ID 验证规则
        $this->addRule('agent_id', function ($value, $data = null) {
            if (!is_string($value)) {
                return false;
            }

            // Agent ID 格式验证
            $pattern = '/^[a-zA-Z0-9_-]{8,64}$/';
            return preg_match($pattern, $value) === 1;
        });

        // 项目名称验证规则
        $this->addRule('project_name', function ($value, $data = null) {
            if (!is_string($value)) {
                return false;
            }

            // 项目名称：字母、数字、下划线、连字符，3-50字符
            $pattern = '/^[a-zA-Z0-9_-]{3,50}$/';
            return preg_match($pattern, $value) === 1;
        });

        // 任务优先级验证规则
        $this->addRule('task_priority', function ($value, $data = null) {
            $validPriorities = ['low', 'medium', 'high', 'urgent'];
            return in_array($value, $validPriorities, true);
        });

        // 任务状态验证规则
        $this->addRule('task_status', function ($value, $data = null) {
            $validStatuses = ['pending', 'in_progress', 'blocked', 'completed', 'cancelled'];
            return in_array($value, $validStatuses, true);
        });

        // GitHub仓库URL验证规则
        $this->addRule('github_url', function ($value, $data = null) {
            if (!is_string($value)) {
                return false;
            }

            $pattern = '/^https:\/\/github\.com\/[a-zA-Z0-9_.-]+\/[a-zA-Z0-9_.-]+$/';
            return preg_match($pattern, $value) === 1;
        });
    }

    /**
     * 转换规则格式为 inhere/validate 格式
     */
    protected function convertRulesToInhereFormat(array $rules): array
    {
        $converted = [];

        foreach ($rules as $field => $rule) {
            if (is_string($rule)) {
                // 处理字符串规则，如 'required|email|min:3'
                $ruleArray = explode('|', $rule);

                foreach ($ruleArray as $singleRule) {
                    if (strpos($singleRule, ':') !== false) {
                        // 处理带参数的规则，如 'min:3'
                        [$validator, $params] = explode(':', $singleRule, 2);
                        $converted[] = [$field, $validator, ...explode(',', $params)];
                    } else {
                        // 处理简单规则，如 'required'
                        $converted[] = [$field, $singleRule];
                    }
                }
            } elseif (is_array($rule)) {
                // 处理数组格式规则
                foreach ($rule as $singleRule) {
                    if (is_array($singleRule)) {
                        $converted[] = array_merge([$field], $singleRule);
                    } else {
                        $converted[] = [$field, $singleRule];
                    }
                }
            }
        }

        return $converted;
    }
}
