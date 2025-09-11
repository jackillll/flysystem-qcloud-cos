<?php

/**
 * 独立PHP项目使用示例
 * 
 * 本示例展示如何在非Laravel环境中使用 flysystem-qcloud-cos 包
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Jackillll\Flysystem\QcloudCos\QcloudCosFactory;

// COS 配置
$config = [
    'region' => 'ap-guangzhou', // 你的地域
    'credentials' => [
        'appId' => 'your-app-id',
        'secretId' => 'your-secret-id',
        'secretKey' => 'your-secret-key',
        // 'token' => 'your-token', // 使用临时密钥时需要
    ],
    'timeout' => 60,
    'connect_timeout' => 60,
    'bucket' => 'your-bucket-name', // 存储桶名称
    'cdn' => '', // CDN 域名（可选）
    'scheme' => 'https',
    'read_from_cdn' => false,
    'cdn_key' => '', // CDN 密钥（可选）
    'encrypt' => false,
];

try {
    // 验证配置
    QcloudCosFactory::validateConfig($config);
    
    // 方法1: 使用工厂类创建基础 Filesystem 实例
    $filesystem = QcloudCosFactory::createFilesystem($config);
    
    // 方法2: 使用工厂类创建扩展适配器（推荐，包含更多功能）
    $extendedAdapter = QcloudCosFactory::createExtendedAdapter($config);
    
    echo "✓ COS 客户端初始化成功\n";
    
    // === 基础文件操作示例 ===
    
    // 写入文件
    $content = 'Hello, Qcloud COS!';
    $path = 'test/hello.txt';
    
    if ($filesystem->write($path, $content)) {
        echo "✓ 文件写入成功: {$path}\n";
    }
    
    // 检查文件是否存在
    if ($filesystem->fileExists($path)) {
        echo "✓ 文件存在: {$path}\n";
    }
    
    // 读取文件
    $readContent = $filesystem->read($path);
    echo "✓ 文件内容: {$readContent}\n";
    
    // 获取文件信息
    $fileSize = $filesystem->fileSize($path);
    echo "✓ 文件大小: {$fileSize} 字节\n";
    
    // 列出目录内容
    $listing = $filesystem->listContents('test', false);
    echo "✓ 目录内容:\n";
    foreach ($listing as $item) {
        echo "  - {$item->path()} ({$item->type()})\n";
    }
    
    // === 扩展功能示例 ===
    
    // 获取文件URL
    $url = $extendedAdapter->url($path);
    echo "✓ 文件URL: {$url}\n";
    
    // 获取临时URL（签名URL）
    $temporaryUrl = $extendedAdapter->temporaryUrl($path, now()->addHour());
    echo "✓ 临时URL: {$temporaryUrl}\n";
    
    // 从远程URL上传文件
    $remoteUrl = 'https://httpbin.org/json';
    $remotePath = 'test/remote-file.json';
    
    if ($extendedAdapter->putRemoteFile($remotePath, $remoteUrl)) {
        echo "✓ 远程文件上传成功: {$remotePath}\n";
    }
    
    // 获取COS客户端（用于高级操作）
    $cosClient = $extendedAdapter->getCOSClient();
    echo "✓ 获取COS客户端成功\n";
    
    // 复制文件
    if ($filesystem->copy($path, 'test/hello-copy.txt')) {
        echo "✓ 文件复制成功\n";
    }
    
    // 移动文件
    if ($filesystem->move('test/hello-copy.txt', 'test/hello-moved.txt')) {
        echo "✓ 文件移动成功\n";
    }
    
    // 删除文件
    if ($filesystem->delete('test/hello-moved.txt')) {
        echo "✓ 文件删除成功\n";
    }
    
    echo "\n🎉 所有操作完成！\n";
    
} catch (Exception $e) {
    echo "❌ 错误: " . $e->getMessage() . "\n";
    echo "堆栈跟踪:\n" . $e->getTraceAsString() . "\n";
}

/**
 * 辅助函数：模拟 Laravel 的 now() 函数
 */
function now() {
    return new \DateTime();
}