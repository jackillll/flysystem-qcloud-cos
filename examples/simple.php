<?php

/**
 * 简单使用示例
 * 
 * 最简单的方式在普通PHP项目中使用 flysystem-qcloud-cos
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Jackillll\Flysystem\QcloudCos\QcloudCosFactory;

// 最小配置
$config = [
    'region' => 'ap-guangzhou',
    'credentials' => [
        'appId' => 'your-app-id',
        'secretId' => 'your-secret-id',
        'secretKey' => 'your-secret-key',
    ],
    'bucket' => 'your-bucket-name',
];

try {
    // 创建扩展适配器（推荐）
    $storage = QcloudCosFactory::createExtendedAdapter($config);
    
    // 上传文件
    $storage->put('hello.txt', 'Hello World!');
    
    // 读取文件
    $content = $storage->get('hello.txt');
    echo "文件内容: {$content}\n";
    
    // 获取文件URL
    $url = $storage->url('hello.txt');
    echo "文件URL: {$url}\n";
    
    // 检查文件是否存在
    if ($storage->exists('hello.txt')) {
        echo "文件存在\n";
    }
    
    // 删除文件
    $storage->delete('hello.txt');
    echo "文件已删除\n";
    
} catch (Exception $e) {
    echo "错误: " . $e->getMessage() . "\n";
}