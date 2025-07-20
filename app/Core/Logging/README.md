# UCore 自定义日志驱动 - 支持文件大小限制的每日轮转

## 功能特点

1. **初始日志文件**：使用基础文件名（如 `laravel.log`）
2. **文件大小限制**：当文件超过指定大小时自动分割
3. **时间限制**：当文件超过60秒未修改时也自动分割
4. **自动备份**：分割后的文件命名为 `laravel-2025-05-26-1.log`、`laravel-2025-05-26-2.log` 等
5. **自动清理**：支持设置保留天数，自动删除过期文件
6. **灵活配置**：支持多种文件大小单位（K、M、G）

## 使用方法

### 1. 环境变量配置

在 `.env` 文件中设置：

```env
# 使用自定义日志驱动
LOG_CHANNEL=size_rotating_daily

# 最大文件大小（支持 K、M、G 单位）
LOG_MAX_FILE_SIZE=100M

# 保留天数
LOG_DAILY_DAYS=14

# 日志级别
LOG_LEVEL=debug
```

### 2. 配置选项说明

| 配置项 | 说明 | 默认值 | 示例 |
|--------|------|--------|------|
| `max_file_size` | 单个文件最大大小 | 100M | 50M, 1G, 512K |
| `days` | 保留天数（0表示不限制） | 14 | 7, 30, 0 |
| `path` | 日志文件路径 | storage/logs/laravel.log | - |
| `level` | 日志级别 | debug | info, warning, error |
| `permission` | 文件权限 | 0777 | 0644, 0755 |
| `locking` | 是否使用文件锁 | false | true, false |

### 3. 文件大小单位

- `K` 或 `KB`：千字节
- `M` 或 `MB`：兆字节  
- `G` 或 `GB`：千兆字节
- 无单位：字节

示例：
- `1024` = 1024 字节
- `10K` = 10 千字节
- `50M` = 50 兆字节
- `2G` = 2 千兆字节

## 文件命名规则

### 基础文件
- `laravel-2025-05-26.log` - 当天的主日志文件

### 分割文件
- `laravel-2025-05-26-1.log` - 第一个分割文件
- `laravel-2025-05-26-2.log` - 第二个分割文件
- `laravel-2025-05-26-3.log` - 第三个分割文件

### 分割逻辑
1. 当主文件大小超过限制时，创建 `-1` 后缀的文件
2. 当 `-1` 文件也超过限制时，创建 `-2` 后缀的文件
3. 以此类推...

## 使用示例

### 基本使用

```php
use Illuminate\Support\Facades\Log;

// 记录日志
Log::info('这是一条信息日志');
Log::error('这是一条错误日志');
Log::debug('这是一条调试日志');
```

### 在特定通道中使用

```php
// 在配置文件中定义多个通道
'channels' => [
    'api' => [
        'driver' => 'size_rotating_daily',
        'path' => storage_path('logs/api.log'),
        'max_file_size' => '50M',
        'days' => 7,
    ],
    'error' => [
        'driver' => 'size_rotating_daily',
        'path' => storage_path('logs/error.log'),
        'max_file_size' => '200M',
        'days' => 30,
        'level' => 'error',
    ],
],

// 使用特定通道
Log::channel('api')->info('API 请求日志');
Log::channel('error')->error('系统错误日志');
```

## 自动清理机制

UCore 提供了自动清理过期日志文件的功能：

### 清理命令
```bash
# 使用配置文件中的保留天数（默认读取 size_rotating_daily.days 配置）
php artisan ucore:clean-size-rotating-logs

# 自定义保留天数
php artisan ucore:clean-size-rotating-logs --days=3

# 试运行（仅显示将要删除的文件）
php artisan ucore:clean-size-rotating-logs --dry-run
```

### 自动计划任务
系统会自动在每天凌晨3点执行清理任务，使用 `size_rotating_daily.days` 配置的保留天数。

## 性能考虑

1. **文件锁定**：在高并发环境下，建议启用 `locking` 选项
2. **文件大小**：建议设置合理的文件大小限制，避免单个文件过大
3. **保留天数**：定期清理旧日志文件，避免磁盘空间不足

## 故障排除

### 权限问题
确保 Laravel 有权限写入日志目录：
```bash
chmod -R 755 storage/logs
chown -R www-data:www-data storage/logs
```

### 磁盘空间
定期检查磁盘空间，确保有足够空间存储日志文件。

### 配置验证
可以通过以下命令测试日志配置：
```bash
php artisan tinker
Log::info('测试日志消息');
```

## 与标准 daily 驱动的区别

| 特性 | 标准 daily | size_rotating_daily |
|------|------------|---------------------|
| 按日期分割 | ✅ | ✅ |
| 按大小分割 | ❌ | ✅ |
| 自动备份 | ❌ | ✅ |
| 文件大小限制 | ❌ | ✅ |
| 保留天数 | ✅ | ✅ |
| 自动清理 | ❌ | ✅ |
| 性能 | 高 | 中等 |

## 注意事项

1. 该驱动会在每次写入日志时检查文件大小，可能会有轻微的性能影响
2. 分割后的文件不会再次分割，如果需要更细粒度的控制，请调整 `max_file_size` 参数
3. 备份文件会自动移动到 `size_rotating_daily` 子目录中进行管理
