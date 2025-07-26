<?php

namespace Modules\Dbcont\Validation;

use Modules\Dbcont\Enums\DatabaseType;

class DatabaseConnectionValidation
{
    /**
     * 验证数据库连接配置
     */
    public static function validate(array $config): array
    {
        $errors = [];

        // 验证必填字段
        $requiredFields = ['name', 'type', 'database'];
        foreach ($requiredFields as $field) {
            if (empty($config[$field])) {
                $errors[] = "字段 {$field} 是必填的";
            }
        }

        // 验证数据库类型
        if (isset($config['type'])) {
            $validTypes = array_column(DatabaseType::cases(), 'value');
            if (!in_array($config['type'], $validTypes)) {
                $errors[] = "无效的数据库类型: {$config['type']}";
            }
        }

        // 验证MySQL/MariaDB特定字段
        if (in_array($config['type'] ?? '', ['MYSQL', 'MARIADB'])) {
            if (empty($config['host'])) {
                $errors[] = 'MySQL/MariaDB 需要主机地址';
            }
            
            if (empty($config['port']) || !is_numeric($config['port']) || $config['port'] < 1 || $config['port'] > 65535) {
                $errors[] = 'MySQL/MariaDB 需要有效的端口号 (1-65535)';
            }
            
            if (empty($config['username'])) {
                $errors[] = 'MySQL/MariaDB 需要用户名';
            }
            
            if (empty($config['password'])) {
                $errors[] = 'MySQL/MariaDB 需要密码';
            }
        }

        // 验证SQLite特定字段
        if ($config['type'] === 'SQLITE') {
            if (empty($config['database']) || !is_string($config['database'])) {
                $errors[] = 'SQLite 需要有效的数据库文件路径';
            }
        }

        // 验证连接选项
        if (isset($config['options']) && !is_array($config['options'])) {
            $errors[] = '连接选项必须是数组';
        }

        return $errors;
    }

    /**
     * 验证连接名称
     */
    public static function validateName(string $name): array
    {
        $errors = [];

        if (empty($name)) {
            $errors[] = '连接名称不能为空';
        }

        if (strlen($name) > 255) {
            $errors[] = '连接名称不能超过255个字符';
        }

        if (!preg_match('/^[a-zA-Z0-9_\-\s]+$/', $name)) {
            $errors[] = '连接名称只能包含字母、数字、下划线、连字符和空格';
        }

        return $errors;
    }

    /**
     * 验证主机地址
     */
    public static function validateHost(string $host): array
    {
        $errors = [];

        if (empty($host)) {
            $errors[] = '主机地址不能为空';
        }

        if (strlen($host) > 255) {
            $errors[] = '主机地址不能超过255个字符';
        }

        // 验证IP地址或域名
        if (!filter_var($host, FILTER_VALIDATE_IP) && !preg_match('/^[a-zA-Z0-9.-]+$/', $host)) {
            $errors[] = '无效的主机地址格式';
        }

        return $errors;
    }

    /**
     * 验证端口号
     */
    public static function validatePort(int $port): array
    {
        $errors = [];

        if ($port < 1 || $port > 65535) {
            $errors[] = '端口号必须在1-65535之间';
        }

        return $errors;
    }

    /**
     * 验证数据库名称
     */
    public static function validateDatabaseName(string $database): array
    {
        $errors = [];

        if (empty($database)) {
            $errors[] = '数据库名称不能为空';
        }

        if (strlen($database) > 255) {
            $errors[] = '数据库名称不能超过255个字符';
        }

        // 验证数据库名称格式
        if (!preg_match('/^[a-zA-Z0-9_\-]+$/', $database)) {
            $errors[] = '数据库名称只能包含字母、数字、下划线和连字符';
        }

        return $errors;
    }

    /**
     * 验证SQLite数据库文件路径
     */
    public static function validateSqlitePath(string $path): array
    {
        $errors = [];

        if (empty($path)) {
            $errors[] = 'SQLite数据库文件路径不能为空';
        }

        // 检查文件是否存在且可读写
        if (file_exists($path) && !is_writable($path)) {
            $errors[] = 'SQLite数据库文件不可写';
        }

        // 检查目录是否可写
        $dir = dirname($path);
        if (!is_dir($dir) || !is_writable($dir)) {
            $errors[] = 'SQLite数据库文件目录不可写';
        }

        return $errors;
    }
}