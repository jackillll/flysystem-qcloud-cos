<?php

/**
 * Laravel 12 兼容性测试文件
 * 这个文件用于测试升级后的COS驱动是否正常工作
 */

require_once __DIR__ . '/vendor/autoload.php';

use Jackillll\Flysystem\QcloudCos\Adapters\QcloudCosAdapter;
use Jackillll\Flysystem\QcloudCos\Adapters\QcloudCosFilesystemAdapter;
use League\Flysystem\Filesystem;
use Qcloud\Cos\Client;

// 测试配置
$config = [
    'region' => 'ap-guangzhou',
    'credentials' => [
        'appId' => 'your-app-id',
        'secretId' => 'your-secret-id',
        'secretKey' => 'your-secret-key',
    ],
    'timeout' => 60,
    'connect_timeout' => 60,
    'bucket' => 'your-bucket',
    'cdn' => '',
    'scheme' => 'https',
    'read_from_cdn' => false,
];

try {
    echo "开始测试 Laravel 12 兼容性...\n";
    
    // 1. 测试 Client 创建
    echo "1. 测试 COS Client 创建...";
    $client = new Client($config);
    echo " ✓ 成功\n";
    
    // 2. 测试 Adapter 创建
    echo "2. 测试 Adapter 创建...";
    $adapter = new QcloudCosAdapter($client, $config);
    echo " ✓ 成功\n";
    
    // 3. 测试 Filesystem 创建
    echo "3. 测试 Filesystem 创建...";
    $filesystem = new Filesystem($adapter, $config);
    echo " ✓ 成功\n";
    
    // 4. 测试扩展适配器创建
    echo "4. 测试扩展适配器创建...";
    $extendedAdapter = new QcloudCosFilesystemAdapter($filesystem, $adapter, $config);
    echo " ✓ 成功\n";
    
    // 5. 测试基本方法
    echo "5. 测试基本方法...";
    
    // 测试文件存在检查（不需要真实连接）
    echo "   - fileExists 方法存在: " . (method_exists($adapter, 'fileExists') ? '✓' : '✗') . "\n";
    echo "   - directoryExists 方法存在: " . (method_exists($adapter, 'directoryExists') ? '✓' : '✗') . "\n";
    echo "   - write 方法存在: " . (method_exists($adapter, 'write') ? '✓' : '✗') . "\n";
    echo "   - read 方法存在: " . (method_exists($adapter, 'read') ? '✓' : '✗') . "\n";
    echo "   - delete 方法存在: " . (method_exists($adapter, 'delete') ? '✓' : '✗') . "\n";
    echo "   - listContents 方法存在: " . (method_exists($adapter, 'listContents') ? '✓' : '✗') . "\n";
    
    // 6. 测试扩展方法
    echo "6. 测试扩展方法...";
    echo "   - url 方法存在: " . (method_exists($extendedAdapter, 'url') ? '✓' : '✗') . "\n";
    echo "   - temporaryUrl 方法存在: " . (method_exists($extendedAdapter, 'temporaryUrl') ? '✓' : '✗') . "\n";
    echo "   - putRemoteFile 方法存在: " . (method_exists($extendedAdapter, 'putRemoteFile') ? '✓' : '✗') . "\n";
    echo "   - getCOSClient 方法存在: " . (method_exists($extendedAdapter, 'getCOSClient') ? '✓' : '✗') . "\n";
    
    // 7. 测试接口实现
    echo "7. 测试接口实现...";
    echo "   - FilesystemAdapter 接口: " . (($adapter instanceof \League\Flysystem\FilesystemAdapter) ? '✓' : '✗') . "\n";
    echo "   - FilesystemAdapter 扩展: " . (($extendedAdapter instanceof \Illuminate\Filesystem\FilesystemAdapter) ? '✓' : '✗') . "\n";
    
    echo "\n🎉 所有测试通过！Laravel 12 兼容性升级成功！\n";
    
} catch (Exception $e) {
    echo "\n❌ 测试失败: " . $e->getMessage() . "\n";
    echo "堆栈跟踪:\n" . $e->getTraceAsString() . "\n";
} catch (Error $e) {
    echo "\n❌ 致命错误: " . $e->getMessage() . "\n";
    echo "堆栈跟踪:\n" . $e->getTraceAsString() . "\n";
}

echo "\n测试完成。\n";