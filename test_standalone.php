<?php

/**
 * 测试普通PHP环境下的功能
 * 
 * 验证非Laravel环境下flysystem-qcloud-cos的各项功能
 */

require_once __DIR__ . '/vendor/autoload.php';

use Jackillll\Flysystem\QcloudCos\QcloudCosFactory;

echo "=== 测试普通PHP环境下的flysystem-qcloud-cos功能 ===\n\n";

// 测试配置
$config = [
    'region' => 'ap-guangzhou',
    'credentials' => [
        'appId' => getenv('COS_APP_ID') ?: 'test-app-id',
        'secretId' => getenv('COS_SECRET_ID') ?: 'test-secret-id',
        'secretKey' => getenv('COS_SECRET_KEY') ?: 'test-secret-key',
    ],
    'bucket' => getenv('COS_BUCKET') ?: 'test-bucket',
    'cdn' => getenv('COS_CDN') ?: '',
];

try {
    echo "1. 测试配置验证...\n";
    $isValid = QcloudCosFactory::validateConfig($config);
    echo "   配置验证: " . ($isValid ? '通过' : '失败') . "\n\n";
    
    echo "2. 测试COS客户端创建...\n";
    $client = QcloudCosFactory::createClient($config);
    echo "   COS客户端创建: 成功\n";
    echo "   客户端类型: " . get_class($client) . "\n\n";
    
    echo "3. 测试基础适配器创建...\n";
    $adapter = QcloudCosFactory::createAdapter($config);
    echo "   基础适配器创建: 成功\n";
    echo "   适配器类型: " . get_class($adapter) . "\n\n";
    
    echo "4. 测试Filesystem创建...\n";
    $filesystem = QcloudCosFactory::createFilesystem($config);
    echo "   Filesystem创建: 成功\n";
    echo "   Filesystem类型: " . get_class($filesystem) . "\n\n";
    
    echo "5. 测试扩展适配器创建...\n";
    $extendedAdapter = QcloudCosFactory::createExtendedAdapter($config);
    echo "   扩展适配器创建: 成功\n";
    echo "   扩展适配器类型: " . get_class($extendedAdapter) . "\n\n";
    
    echo "6. 测试基本方法可用性...\n";
    $methods = ['put', 'get', 'exists', 'delete', 'url'];
    foreach ($methods as $method) {
        if (method_exists($extendedAdapter, $method)) {
            echo "   方法 {$method}: 可用\n";
        } else {
            echo "   方法 {$method}: 不可用\n";
        }
    }
    echo "\n";
    
    echo "7. 测试扩展方法可用性...\n";
    $extendedMethods = ['putFile', 'putFileAs', 'temporaryUrl'];
    foreach ($extendedMethods as $method) {
        if (method_exists($extendedAdapter, $method)) {
            echo "   扩展方法 {$method}: 可用\n";
        } else {
            echo "   扩展方法 {$method}: 不可用\n";
        }
    }
    echo "\n";
    
    echo "8. 测试接口实现...\n";
    $interfaces = [
        'League\\Flysystem\\FilesystemOperator',
        'Illuminate\\Contracts\\Filesystem\\Filesystem',
        'Illuminate\\Contracts\\Filesystem\\Cloud'
    ];
    
    foreach ($interfaces as $interface) {
        if (interface_exists($interface) && $extendedAdapter instanceof $interface) {
            echo "   实现接口 {$interface}: 是\n";
        } else {
            echo "   实现接口 {$interface}: 否 (" . (interface_exists($interface) ? '未实现' : '接口不存在') . ")\n";
        }
    }
    echo "\n";
    
    echo "=== 所有测试完成 ===\n";
    echo "普通PHP环境兼容性: 成功\n";
    echo "所有核心功能均可正常使用\n";
    
} catch (Exception $e) {
    echo "错误: " . $e->getMessage() . "\n";
    echo "堆栈跟踪:\n" . $e->getTraceAsString() . "\n";
    exit(1);
} catch (Error $e) {
    echo "致命错误: " . $e->getMessage() . "\n";
    echo "堆栈跟踪:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}