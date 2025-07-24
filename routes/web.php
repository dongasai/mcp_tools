<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Task模型测试路由
require __DIR__ . '/../app/Modules/Task/routes/test.php';

Route::get('/timeout', function () {
    // 设置响应头，禁用缓冲
    header('Content-Type: text/plain; charset=utf-8');
    header('Cache-Control: no-cache');
    header('X-Accel-Buffering: no');
    
    echo "开始测试超时时间...\n";
    echo "当前时间: " . date('Y-m-d H:i:s') . "\n";
    echo "PHP最大执行时间: " . ini_get('max_execution_time') . "秒\n";
    echo "内存限制: " . ini_get('memory_limit') . "\n";
    echo "=====================================\n";
    
    $counter = 0;
    while (true) {
        $counter++;
        echo "第 {$counter} 秒 - " . date('H:i:s') . "\n";
        
        // 刷新输出缓冲区
        if (ob_get_level()) {
            ob_flush();
        }
        flush();
        
        // 等待1秒
        sleep(1);
        
        // 可选：设置一个最大测试时间，防止真的无限运行
        if ($counter >= 3600) { // 最多运行1小时
            echo "测试结束，已达到最大测试时间\n";
            break;
        }
    }
    
    echo "测试完成\n";
    echo "结束时间: " . date('Y-m-d H:i:s') . "\n";
});
