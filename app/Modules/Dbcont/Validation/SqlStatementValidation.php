<?php

namespace App\Modules\Dbcont\Validation;

class SqlStatementValidation
{
    /**
     * 验证SQL语句
     */
    public static function validate(string $sql): array
    {
        $errors = [];

        if (empty(trim($sql))) {
            $errors[] = 'SQL语句不能为空';
            return $errors;
        }

        // 检查SQL长度
        if (strlen($sql) > 10000) {
            $errors[] = 'SQL语句过长，最大长度为10000字符';
        }

        // 检查基本语法
        if (!self::hasValidSyntax($sql)) {
            $errors[] = 'SQL语句语法无效';
        }

        // 检查危险操作
        $dangerousOperations = self::checkDangerousOperations($sql);
        if (!empty($dangerousOperations)) {
            $errors[] = 'SQL语句包含危险操作: ' . implode(', ', $dangerousOperations);
        }

        // 检查注入风险
        if (self::hasInjectionRisk($sql)) {
            $errors[] = 'SQL语句存在注入风险';
        }

        return $errors;
    }

    /**
     * 检查基本语法
     */
    private static function hasValidSyntax(string $sql): bool
    {
        $sql = trim($sql);
        
        // 必须以有效的SQL关键字开头
        $validKeywords = [
            'SELECT', 'INSERT', 'UPDATE', 'DELETE', 'CREATE', 'ALTER', 'DROP',
            'TRUNCATE', 'SHOW', 'DESCRIBE', 'EXPLAIN', 'WITH'
        ];
        
        $upperSql = strtoupper($sql);
        $startsWithValid = false;
        
        foreach ($validKeywords as $keyword) {
            if (strpos($upperSql, $keyword) === 0) {
                $startsWithValid = true;
                break;
            }
        }
        
        if (!$startsWithValid) {
            return false;
        }

        // 检查括号匹配
        if (!self::checkBrackets($sql)) {
            return false;
        }

        // 检查引号匹配
        if (!self::checkQuotes($sql)) {
            return false;
        }

        return true;
    }

    /**
     * 检查危险操作
     */
    private static function checkDangerousOperations(string $sql): array
    {
        $dangerousOperations = [];
        $upperSql = strtoupper($sql);

        $dangerousPatterns = [
            'DROP DATABASE' => '删除数据库',
            'DROP TABLE' => '删除表',
            'DROP INDEX' => '删除索引',
            'TRUNCATE TABLE' => '清空表',
            'ALTER TABLE DROP' => '删除表列',
            'DELETE FROM' => '删除数据',
            'UPDATE.*SET.*=.*--' => '潜在的注入攻击',
            'INSERT INTO.*VALUES.*--' => '潜在的注入攻击',
            'UNION.*SELECT' => '联合查询注入',
            'EXEC\(' => '执行命令',
            'EXECUTE\(' => '执行命令',
            'XP_' => '扩展存储过程',
            'SP_' => '存储过程',
            'SHUTDOWN' => '关闭数据库',
            'BACKUP DATABASE' => '备份数据库',
            'RESTORE DATABASE' => '恢复数据库',
        ];

        foreach ($dangerousPatterns as $pattern => $description) {
            if (preg_match('/' . $pattern . '/i', $sql)) {
                $dangerousOperations[] = $description;
            }
        }

        return $dangerousOperations;
    }

    /**
     * 检查注入风险
     */
    private static function hasInjectionRisk(string $sql): bool
    {
        $sql = strtoupper($sql);

        // 检查常见的注入模式
        $injectionPatterns = [
            'OR 1=1',
            'OR TRUE',
            'OR 0=0',
            '--',
            '#',
            '/*',
            '*/',
            'UNION SELECT',
            'UNION ALL SELECT',
            'WAITFOR DELAY',
            'SHUTDOWN',
            'BACKUP DATABASE',
            'RESTORE DATABASE',
        ];

        foreach ($injectionPatterns as $pattern) {
            if (strpos($sql, $pattern) !== false) {
                return true;
            }
        }

        // 检查可疑的字符组合
        $suspiciousPatterns = [
            '/\bOR\b.*\b(1=1|TRUE|0=0)\b/i',
            '/\bAND\b.*\b(1=1|TRUE|0=0)\b/i',
            '/\'.*OR.*\'.*=.*\'/i',
            '/\".*OR.*\".*=.*\"/i',
            '/\bUNION\b.*\bSELECT\b/i',
            '/\bEXEC\b.*\(/i',
            '/\bEXECUTE\b.*\(/i',
        ];

        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $sql)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 检查括号匹配
     */
    private static function checkBrackets(string $sql): bool
    {
        $stack = [];
        $length = strlen($sql);
        
        for ($i = 0; $i < $length; $i++) {
            $char = $sql[$i];
            
            if ($char === '(') {
                $stack[] = '(';
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
    private static function checkQuotes(string $sql): bool
    {
        $inSingleQuote = false;
        $inDoubleQuote = false;
        $length = strlen($sql);
        
        for ($i = 0; $i < $length; $i++) {
            $char = $sql[$i];
            
            if ($char === "'" && !$inDoubleQuote) {
                if ($i > 0 && $sql[$i - 1] === '\\') {
                    continue;
                }
                $inSingleQuote = !$inSingleQuote;
            } elseif ($char === '"' && !$inSingleQuote) {
                if ($i > 0 && $sql[$i - 1] === '\\') {
                    continue;
                }
                $inDoubleQuote = !$inDoubleQuote;
            }
        }
        
        return !$inSingleQuote && !$inDoubleQuote;
    }

    /**
     * 验证SQL语句类型
     */
    public static function validateStatementType(string $sql, array $allowedTypes): array
    {
        $errors = [];
        $sql = trim(strtoupper($sql));
        
        $statementType = self::getStatementType($sql);
        
        if (!in_array($statementType, $allowedTypes)) {
            $errors[] = "不允许的SQL语句类型: {$statementType}";
        }
        
        return $errors;
    }

    /**
     * 获取SQL语句类型
     */
    public static function getStatementType(string $sql): string
    {
        $sql = trim(strtoupper($sql));
        
        if (strpos($sql, 'SELECT') === 0) {
            return 'SELECT';
        } elseif (strpos($sql, 'INSERT') === 0) {
            return 'INSERT';
        } elseif (strpos($sql, 'UPDATE') === 0) {
            return 'UPDATE';
        } elseif (strpos($sql, 'DELETE') === 0) {
            return 'DELETE';
        } elseif (strpos($sql, 'CREATE') === 0) {
            return 'CREATE';
        } elseif (strpos($sql, 'ALTER') === 0) {
            return 'ALTER';
        } elseif (strpos($sql, 'DROP') === 0) {
            return 'DROP';
        } elseif (strpos($sql, 'TRUNCATE') === 0) {
            return 'TRUNCATE';
        } elseif (strpos($sql, 'SHOW') === 0) {
            return 'SHOW';
        } elseif (strpos($sql, 'DESCRIBE') === 0) {
            return 'DESCRIBE';
        } elseif (strpos($sql, 'EXPLAIN') === 0) {
            return 'EXPLAIN';
        } else {
            return 'UNKNOWN';
        }
    }

    /**
     * 验证表名
     */
    public static function validateTableName(string $tableName): array
    {
        $errors = [];

        if (empty($tableName)) {
            $errors[] = '表名不能为空';
        }

        if (strlen($tableName) > 255) {
            $errors[] = '表名不能超过255个字符';
        }

        if (!preg_match('/^[a-zA-Z0-9_]+$/', $tableName)) {
            $errors[] = '表名只能包含字母、数字和下划线';
        }

        return $errors;
    }

    /**
     * 验证列名
     */
    public static function validateColumnName(string $columnName): array
    {
        $errors = [];

        if (empty($columnName)) {
            $errors[] = '列名不能为空';
        }

        if (strlen($columnName) > 255) {
            $errors[] = '列名不能超过255个字符';
        }

        if (!preg_match('/^[a-zA-Z0-9_]+$/', $columnName)) {
            $errors[] = '列名只能包含字母、数字和下划线';
        }

        return $errors;
    }
}