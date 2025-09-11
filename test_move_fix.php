<?php

require_once 'vendor/autoload.php';

use Jackillll\Flysystem\QcloudCos\Adapters\QcloudCosAdapter;
use League\Flysystem\Config;

echo "=== 测试移动文件修复逻辑 ===\n\n";

// 创建一个模拟的适配器来测试move方法的逻辑
class TestQcloudCosAdapter extends QcloudCosAdapter
{
    private $shouldCopyFail = false;
    private $shouldDeleteFail = false;
    private $files = [];
    
    public function __construct()
    {
        // 不调用父构造函数，避免需要真实的COS配置
    }
    
    public function setShouldCopyFail(bool $fail)
    {
        $this->shouldCopyFail = $fail;
    }
    
    public function setShouldDeleteFail(bool $fail)
    {
        $this->shouldDeleteFail = $fail;
    }
    
    public function copy(string $source, string $destination, Config $config): void
    {
        if ($this->shouldCopyFail) {
            throw new Exception('Copy failed');
        }
        
        if (!isset($this->files[$source])) {
            throw new Exception('Source file not found');
        }
        
        $this->files[$destination] = $this->files[$source];
        echo "   模拟复制: {$source} -> {$destination}\n";
    }
    
    public function delete(string $path): void
    {
        if ($this->shouldDeleteFail) {
            throw new Exception('Delete failed');
        }
        
        unset($this->files[$path]);
        echo "   模拟删除: {$path}\n";
    }
    
    public function write(string $path, string $contents, Config $config): void
    {
        $this->files[$path] = $contents;
        echo "   模拟写入: {$path}\n";
    }
    
    public function fileExists(string $path): bool
    {
        return isset($this->files[$path]);
    }
    
    public function getFiles(): array
    {
        return $this->files;
    }
}

// 测试场景1：正常移动操作
echo "1. 测试正常移动操作...\n";
$adapter = new TestQcloudCosAdapter();
$config = new Config();

// 创建源文件
$adapter->write('temp/test.txt', 'test content', $config);
echo "   源文件存在: " . ($adapter->fileExists('temp/test.txt') ? '是' : '否') . "\n";

try {
    $adapter->move('temp/test.txt', 'final/test.txt', $config);
    echo "   ✓ 移动操作成功\n";
    echo "   源文件存在: " . ($adapter->fileExists('temp/test.txt') ? '是' : '否') . "\n";
    echo "   目标文件存在: " . ($adapter->fileExists('final/test.txt') ? '是' : '否') . "\n";
} catch (Exception $e) {
    echo "   ✗ 移动操作失败: " . $e->getMessage() . "\n";
}

echo "\n2. 测试复制失败的情况...\n";
$adapter2 = new TestQcloudCosAdapter();
$adapter2->write('temp/test2.txt', 'test content 2', $config);
$adapter2->setShouldCopyFail(true);

echo "   源文件存在: " . ($adapter2->fileExists('temp/test2.txt') ? '是' : '否') . "\n";

try {
    $adapter2->move('temp/test2.txt', 'final/test2.txt', $config);
    echo "   ✗ 移动操作应该失败但成功了\n";
} catch (Exception $e) {
    echo "   ✓ 移动操作正确失败: " . $e->getMessage() . "\n";
    echo "   源文件存在: " . ($adapter2->fileExists('temp/test2.txt') ? '是' : '否') . "\n";
    echo "   目标文件存在: " . ($adapter2->fileExists('final/test2.txt') ? '是' : '否') . "\n";
}

echo "\n3. 测试复制成功但删除失败的情况...\n";
$adapter3 = new TestQcloudCosAdapter();
$adapter3->write('temp/test3.txt', 'test content 3', $config);
$adapter3->setShouldDeleteFail(true);

echo "   源文件存在: " . ($adapter3->fileExists('temp/test3.txt') ? '是' : '否') . "\n";

// 捕获错误日志
ob_start();
try {
    $adapter3->move('temp/test3.txt', 'final/test3.txt', $config);
    echo "   ✓ 移动操作成功（即使删除失败）\n";
    echo "   源文件存在: " . ($adapter3->fileExists('temp/test3.txt') ? '是' : '否') . "\n";
    echo "   目标文件存在: " . ($adapter3->fileExists('final/test3.txt') ? '是' : '否') . "\n";
} catch (Exception $e) {
    echo "   移动操作异常: " . $e->getMessage() . "\n";
}
$output = ob_get_clean();
echo $output;

echo "\n=== 测试完成 ===\n";
echo "\n修复说明：\n";
echo "- 修改了move方法，确保只有在copy成功后才执行delete操作\n";
echo "- 如果delete失败，会记录警告但不影响move操作的成功状态\n";
echo "- 这样可以避免'只删除临时文件而没有移动'的问题\n";