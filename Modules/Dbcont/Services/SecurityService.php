<?php

namespace Modules\Dbcont\Services;

use Modules\Dbcont\Enums\PermissionLevel;

class SecurityService
{
    /**
     * 验证SQL语句安全性
     */
    public function validateSql(string $sql, PermissionLevel $level): bool
    {
        // 标准化SQL
        $sql = trim(strtoupper($sql));
        
        // 检查是否为空
        if (empty($sql)) {
            return false;
        }

        // 检查禁止的关键字
        if (!$this->checkAllowedKeywords($sql, $level)) {
            return false;
        }

        // 检查SQL注入风险
        if ($this->hasSqlInjectionRisk($sql)) {
            return false;
        }

        // 检查语法有效性
        if (!$this->validateSyntax($sql)) {
            return false;
        }

        return true;
    }

    /**
     * 检查允许的关键字
     */
    private function checkAllowedKeywords(string $sql, PermissionLevel $level): bool
    {
        $deniedKeywords = config('dbcont.security.denied_keywords', []);
        
        // 根据权限级别添加额外的限制
        switch ($level) {
            case PermissionLevel::READ_ONLY:
                $allowedOperations = ['SELECT'];
                break;
            
            case PermissionLevel::READ_WRITE:
                $allowedOperations = ['SELECT', 'INSERT', 'UPDATE', 'DELETE'];
                break;
            
            case PermissionLevel::ADMIN:
                $allowedOperations = config('dbcont.security.allowed_operations', []);
                break;
            
            default:
                return false;
        }

        // 检查是否包含禁止的关键字
        foreach ($deniedKeywords as $keyword) {
            if (stripos($sql, $keyword) !== false) {
                return false;
            }
        }

        // 检查是否以允许的操作开头
        $startsWithAllowed = false;
        foreach ($allowedOperations as $operation) {
            if (stripos(trim($sql), $operation) === 0) {
                $startsWithAllowed = true;
                break;
            }
        }

        if (!$startsWithAllowed) {
            return false;
        }

        return true;
    }

    /**
     * 检查SQL注入风险
     */
    private function hasSqlInjectionRisk(string $sql): bool
    {
        // 检查常见的SQL注入模式
        $injectionPatterns = [
            '/(\bOR\b|\bAND\b)\s+\d+\s*=\s*\d+/i',  // OR 1=1
            '/(\bOR\b|\bAND\b)\s*["\'][^"\']*["\']\s*=\s*["\'][^"\']*["\']/i',  // OR 'x'='x'
            '/\bUNION\b.*\bSELECT\b/i',  // UNION SELECT
            '/\bINSERT\b.*\bINTO\b.*\bVALUES\b/i',  // INSERT INTO
            '/\bUPDATE\b.*\bSET\b/i',  // UPDATE SET
            '/\bDELETE\b.*\bFROM\b/i',  // DELETE FROM
            '/\bDROP\b.*\bTABLE\b/i',  // DROP TABLE
            '/\bALTER\b.*\bTABLE\b/i',  // ALTER TABLE
            '/\bCREATE\b.*\bTABLE\b/i',  // CREATE TABLE
            '/\bEXEC\b|\bEXECUTE\b/i',  // EXEC
            '/\bXP_/i',  // SQL Server扩展存储过程
            '/\bSP_/i',  // SQL Server存储过程
            '/;.*\b(SELECT|INSERT|UPDATE|DELETE|DROP|ALTER|CREATE)\b/i',  // 多语句
            '/\bWAITFOR\b.*\bDELAY\b/i',  // WAITFOR DELAY
            '/\bSHUTDOWN\b/i',  // SHUTDOWN
            '/\bBACKUP\b.*\bDATABASE\b/i',  // BACKUP DATABASE
            '/\bRESTORE\b.*\bDATABASE\b/i',  // RESTORE DATABASE
        ];

        foreach ($injectionPatterns as $pattern) {
            if (preg_match($pattern, $sql)) {
                return true;
            }
        }

        // 检查注释
        if (preg_match('/(--|#|\/\*)/', $sql)) {
            return true;
        }

        return false;
    }

    /**
     * 验证SQL语法
     */
    private function validateSyntax(string $sql): bool
    {
        // 基本的语法检查
        $sql = trim($sql);
        
        // 检查是否以分号结尾
        if (substr($sql, -1) === ';') {
            $sql = substr($sql, 0, -1);
        }
        
        // 检查括号是否匹配
        if (!$this->checkBrackets($sql)) {
            return false;
        }
        
        // 检查引号是否匹配
        if (!$this->checkQuotes($sql)) {
            return false;
        }
        
        return true;
    }

    /**
     * 检查括号匹配
     */
    private function checkBrackets(string $sql): bool
    {
        $stack = [];
        $length = strlen($sql);
        
        for ($i = 0; $i < $length; $i++) {
            $char = $sql[$i];
            
            if ($char === '(') {
                $stack[] = $char;
            } elseif ($char === ')') {
                if (empty($stack)) {
                    return false;
                }
                array_pop($stack);
            }
        }
        
        return empty($stack);
    }

    /**
     * 检查引号匹配
     */
    private function checkQuotes(string $sql): bool
    {
        $inSingleQuote = false;
        $inDoubleQuote = false;
        $escaped = false;
        $length = strlen($sql);
        
        for ($i = 0; $i < $length; $i++) {
            $char = $sql[$i];
            
            if ($escaped) {
                $escaped = false;
                continue;
            }
            
            if ($char === '\\') {
                $escaped = true;
                continue;
            }
            
            if ($char === "'" && !$inDoubleQuote) {
                $inSingleQuote = !$inSingleQuote;
            } elseif ($char === '"' && !$inSingleQuote) {
                $inDoubleQuote = !$inDoubleQuote;
            }
        }
        
        return !$inSingleQuote && !$inDoubleQuote;
    }

    /**
     * 过滤敏感数据
     */
    public function filterSensitiveData(array $data, array $sensitiveFields = []): array
    {
        if (empty($sensitiveFields)) {
            $sensitiveFields = ['password', 'passwd', 'pwd', 'secret', 'token', 'key'];
        }
        
        array_walk_recursive($data, function (&$value, $key) use ($sensitiveFields) {
            foreach ($sensitiveFields as $field) {
                if (stripos($key, $field) !== false) {
                    $value = '***';
                    break;
                }
            }
        });
        
        return $data;
    }

    /**
     * 检查IP是否在白名单中
     */
    public function isIpAllowed(string $ip): bool
    {
        if (!config('dbcont.security.enable_ip_whitelist', false)) {
            return true;
        }
        
        $whitelist = config('dbcont.security.ip_whitelist', []);
        
        if (empty($whitelist)) {
            return true;
        }
        
        return in_array($ip, $whitelist);
    }

    /**
     * 获取查询限制
     */
    public function getQueryLimits(): array
    {
        return [
            'max_execution_time' => config('dbcont.default.timeout', 30),
            'max_result_rows' => config('dbcont.default.max_result_rows', 1000),
            'max_result_size' => config('dbcont.default.max_result_size', '10MB'),
        ];
    }
}