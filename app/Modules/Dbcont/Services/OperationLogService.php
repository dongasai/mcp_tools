<?php

namespace App\Modules\Dbcont\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class OperationLogService
{
    /**
     * 记录操作日志（符合AiWork规范）
     * 
     * @param string $title 操作标题（将用于文件名）
     * @param array $data 日志数据（自动转为YAML格式）
     * @param string $module 模块名称（默认Dbcont）
     */
    public function log(string $title, array $data, string $module = 'Dbcont'): void
    {
        // 生成符合AiWork规范的文件路径
        $datePath = now()->format('Y年m月');
        $timePrefix = now()->format('dHi');
        $filename = "{$timePrefix}-" . Str::slug($title) . '.md';
        $path = "AiWork/{$datePath}/{$filename}";

        // 构建日志内容（Markdown格式）
        $content = "# {$title}\n\n";
        $content .= "**模块**: {$module}\n";
        $content .= "**时间**: " . now()->toDateTimeString() . "\n\n";
        $content .= "## 操作详情\n```yaml\n" . $this->arrayToYaml($data) . "\n```";

        // 确保目录存在并写入文件
        Storage::disk('local')->makeDirectory(dirname($path));
        Storage::disk('local')->put($path, $content);
    }

    /**
     * 将数组转换为YAML格式
     */
    private function arrayToYaml(array $data, int $indent = 0): string
    {
        $yaml = '';
        foreach ($data as $key => $value) {
            $prefix = str_repeat(' ', $indent);
            
            if (is_array($value)) {
                $yaml .= "{$prefix}{$key}:\n" . $this->arrayToYaml($value, $indent + 2);
            } else {
                $yaml .= "{$prefix}{$key}: " . (is_string($value) ? '"' . addslashes($value) . '"' : $value) . "\n";
            }
        }
        return $yaml;
    }
}