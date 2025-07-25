<?php

namespace App\Modules\Core\Validators;

use Illuminate\Support\Facades\Validator;

/**
 * 简单验证器类 - 不使用服务包裹
 */
class SimpleValidator
{
    protected array $errors = [];



    /**
     * 验证数据
     */
    public function validate(array $data, array $rules, array $messages = []): array
    {
        $this->errors = [];

        $validator = Validator::make($data, $rules, $messages);

        if ($validator->passes()) {
            return $validator->validated();
        }

        $this->errors = $validator->errors()->toArray();
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
     * 获取第一个错误
     */
    public function getFirstError(): string
    {
        if (empty($this->errors)) {
            return '';
        }

        $firstField = array_key_first($this->errors);
        $firstError = $this->errors[$firstField];

        return is_array($firstError) ? $firstError[0] : $firstError;
    }

    /**
     * 是否有错误
     */
    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }



    /**
     * 静态验证方法 - 快速验证
     */
    public static function check(array $data, array $rules, array $messages = []): array
    {
        $validator = new static();
        return $validator->validate($data, $rules, $messages);
    }

    /**
     * 静态验证方法 - 返回验证器实例
     */
    public static function make(array $data, array $rules, array $messages = []): static
    {
        $validator = new static();
        $validator->validate($data, $rules, $messages);
        return $validator;
    }

    /**
     * 验证用户注册数据
     */
    public static function validateUserRegistration(array $data): array
    {
        $rules = [
            'name' => 'required|string|min:2|max:255',
            'email' => 'required|email',
            'password' => 'required|string|min:8',
        ];

        return static::check($data, $rules);
    }

    /**
     * 验证用户登录数据
     */
    public static function validateUserLogin(array $data): array
    {
        $rules = [
            'email' => 'required|email',
            'password' => 'required|string',
        ];

        return static::check($data, $rules);
    }

    /**
     * 验证用户资料更新数据
     */
    public static function validateUserProfile(array $data): array
    {
        $rules = [
            'name' => 'string|min:2|max:255',
            'timezone' => 'string',
            'locale' => 'string',
        ];

        return static::check($data, $rules);
    }

    /**
     * 验证密码修改数据
     */
    public static function validatePasswordChange(array $data): array
    {
        $rules = [
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8',
        ];

        return static::check($data, $rules);
    }

    /**
     * 验证MCP消息数据
     */
    public static function validateMCPMessage(array $data): array
    {
        $rules = [
            'jsonrpc' => 'required|string',
            'method' => 'required|string',
            'id' => 'required',
        ];

        return static::check($data, $rules);
    }
}
