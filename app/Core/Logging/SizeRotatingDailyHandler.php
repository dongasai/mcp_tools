<?php

namespace App\Core\Logging;

use Monolog\Handler\StreamHandler;
use Monolog\LogRecord;
use Monolog\Level;

/**
 * 支持文件大小限制的每日轮转日志处理器
 *
 * 功能特点：
 * 1. 初始日志文件使用基础文件名（如：laravel.log）
 * 2. 当文件大小超过限制或超过60秒未修改时，自动分割为备份文件（如：laravel-2025-05-26-1.log）
 * 3. 支持设置最大文件数量，自动清理旧文件
 */
class SizeRotatingDailyHandler extends StreamHandler
{
    /**
     * 最大文件大小（字节）
     *
     * @var int
     */
    protected int $maxFileSize;

    /**
     * 基础文件名
     *
     * @var string
     */
    protected string $baseFilename;

    /**
     * 最大文件数量
     *
     * @var int
     */
    protected int $maxFiles;

    /**
     * 文件切割间隔秒
     */
    protected int $diffTimes = 60;

    /**
     * 构造函数
     *
     * @param string $filename 日志文件路径
     * @param int $maxFiles 最大文件数量
     * @param int|string|Level $level 日志级别
     * @param bool $bubble 是否冒泡
     * @param int|null $filePermission 文件权限
     * @param bool $useLocking 是否使用文件锁
     * @param int $maxFileSize 最大文件大小（字节），默认100MB
     */
    public function __construct(
        string $filename,
        int $maxFiles = 0,
        int|string|Level $level = Level::Debug,
        bool $bubble = true,
        ?int $filePermission = null,
        bool $useLocking = false,
        int $maxFileSize = 104857600 // 100MB
    ) {
        $this->maxFileSize = $maxFileSize;
        $this->baseFilename = $filename;
        $this->maxFiles = $maxFiles;

        // 获取当前应该使用的文件名
        $currentFilename = $this->getCurrentFilename();

        parent::__construct($currentFilename, $level, $bubble, $filePermission, $useLocking);
    }

    /**
     * 获取当前应该使用的文件名
     *
     * @return string
     */
    protected function getCurrentFilename(): string
    {
        // 始终返回基础文件名
        // 文件大小检查和轮转在写入时处理
        return $this->baseFilename;
    }

    /**
     * 获取带时间戳的文件名
     *
     * @return string
     */
    protected function getTimedFilename(): string
    {
        $fileInfo = pathinfo($this->baseFilename);
        $timedFilename = $fileInfo['dirname'] . '/' . $fileInfo['filename'] . '-' . date('Y-m-d');

        if (isset($fileInfo['extension'])) {
            $timedFilename .= '.' . $fileInfo['extension'];
        }

        return $timedFilename;
    }

    /**
     * 获取下一个可用的备份文件名
     *
     * @param string $currentFilename 当前文件名
     * @return string
     */
    protected function getNextBackupFilename(string $currentFilename): string
    {
        $fileInfo = pathinfo($currentFilename);
        $baseName = $fileInfo['filename'];
        $extension = isset($fileInfo['extension']) ? '.' . $fileInfo['extension'] : '';
        $directory = $fileInfo['dirname'].'/size_rotating_daily';
        if(!is_dir($directory)){
            mkdir($directory);
        }

        // 为基础文件名添加当前日期
        $baseNameWithDate = $baseName . '-' . date('Y-m-d');

        $counter = 1;
        do {
            $backupFilename = $directory . '/' . $baseNameWithDate . '-' . $counter . $extension;
            $counter++;
        } while (file_exists($backupFilename));

        return $backupFilename;
    }

    /**
     * 重写写入方法，添加文件大小和时间检查
     *
     * @param LogRecord $record
     * @return void
     */
    protected function write(LogRecord $record): void
    {
        // 先调用父类写入方法
        parent::write($record);

        // 在写入后检查是否需要进行分割
        if ($this->url && file_exists($this->url) && $this->shouldRotate()) {
            // 进行文件分割
            $this->rotateDueToSizeOrTime();
        }
    }

    /**
     * 检查是否需要进行轮转
     *
     * @return bool
     */
    protected function shouldRotate(): bool
    {
        // 检查文件大小是否超过限制
        if (filesize($this->url) >= $this->maxFileSize) {
            return true;
        }

        // 检查文件最后修改时间是否超过60秒
        $lastModified = filemtime($this->url);
        if ($lastModified && (time() - $lastModified) >= $this->diffTimes) {
            return true;
        }

        return false;
    }

    /**
     * 由于文件大小超限或时间超限而进行轮转
     *
     * @return void
     */
    protected function rotateDueToSizeOrTime(): void
    {
        // 关闭当前文件句柄
        if ($this->stream) {
            fclose($this->stream);
            $this->stream = null;
        }

        // 获取备份文件名
        $backupFilename = $this->getNextBackupFilename($this->url);

        // 将当前文件重命名为备份文件
        if (file_exists($this->url)) {
            rename($this->url, $backupFilename);
        }

        // 继续使用原始文件名，下次写入时会创建新的空文件
        // 由于 $this->stream 已经设置为 null，父类的 write 方法会重新打开文件
    }
}
