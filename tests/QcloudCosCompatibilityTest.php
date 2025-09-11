<?php

namespace Jackillll\Filesystem\QcloudCos\Tests;

use Jackillll\Filesystem\QcloudCos\Adapters\QcloudCosAdapter;
use Jackillll\Filesystem\QcloudCos\Adapters\QcloudCosFilesystemAdapter;
use League\Flysystem\Config;
use League\Flysystem\Filesystem;
use PHPUnit\Framework\TestCase;
use Qcloud\Cos\Client;

/**
 * Laravel 12 Compatibility Test for Tencent Cloud COS.
 */
class QcloudCosCompatibilityTest extends TestCase
{
    private $adapter;
    private $filesystem;
    private $extendedAdapter;
    private $config;

    protected function setUp(): void
    {
        $this->config = [
            'region' => 'ap-guangzhou',
            'credentials' => [
                'appId' => 'test-app-id',
                'secretId' => 'test-secret-id',
                'secretKey' => 'test-secret-key',
            ],
            'timeout' => 60,
            'connect_timeout' => 60,
            'bucket' => 'test-bucket',
            'cdn' => 'https://cdn.example.com',
            'scheme' => 'https',
            'read_from_cdn' => false,
        ];

        // 注意：这里使用模拟配置，实际测试需要真实的COS配置
        try {
            $client = new Client($this->config);
            $this->adapter = new QcloudCosAdapter($client, $this->config);
            $this->filesystem = new Filesystem($this->adapter, $this->config);
            $this->extendedAdapter = new QcloudCosFilesystemAdapter(
                $this->filesystem,
                $this->adapter,
                $this->config
            );
        } catch (\Exception $e) {
            $this->markTestSkipped('COS配置不可用，跳过测试: ' . $e->getMessage());
        }
    }

    public function testAdapterImplementsFilesystemAdapter()
    {
        $this->assertInstanceOf(
            \League\Flysystem\FilesystemAdapter::class,
            $this->adapter,
            'Adapter应该实现FilesystemAdapter接口'
        );
    }

    public function testExtendedAdapterExtendsFilesystemAdapter()
    {
        $this->assertInstanceOf(
            \Illuminate\Filesystem\FilesystemAdapter::class,
            $this->extendedAdapter,
            'QcloudCosFilesystemAdapter应该继承Laravel的FilesystemAdapter'
        );
    }

    public function testAdapterHasRequiredMethods()
    {
        $requiredMethods = [
            'fileExists',
            'directoryExists', 
            'write',
            'writeStream',
            'read',
            'readStream',
            'delete',
            'deleteDirectory',
            'createDirectory',
            'setVisibility',
            'visibility',
            'mimeType',
            'lastModified',
            'fileSize',
            'listContents',
            'move',
            'copy'
        ];

        foreach ($requiredMethods as $method) {
            $this->assertTrue(
                method_exists($this->adapter, $method),
                "Adapter应该有{$method}方法"
            );
        }
    }

    public function testExtendedAdapterHasPluginMethods()
    {
        $pluginMethods = [
            'url',
            'temporaryUrl',
            'putRemoteFile',
            'putRemoteFileAs',
            'getCdnUrl',
            'getCloudInfiniteUrl',
            'getCOSClient',
            'getAuthorization'
        ];

        foreach ($pluginMethods as $method) {
            $this->assertTrue(
                method_exists($this->extendedAdapter, $method),
                "扩展适配器应该有{$method}方法（原插件功能）"
            );
        }
    }

    public function testConfigMethods()
    {
        $this->assertEquals('test-bucket-test-app-id', $this->adapter->getBucketWithAppId());
        $this->assertEquals('test-bucket', $this->adapter->getBucket());
        $this->assertEquals('test-app-id', $this->adapter->getAppId());
        $this->assertEquals('ap-guangzhou', $this->adapter->getRegion());
    }

    public function testUrlGeneration()
    {
        $path = 'test/file.jpg';
        
        // 测试基本URL生成
        $url = $this->extendedAdapter->url($path);
        $this->assertIsString($url, 'URL应该是字符串');
        
        // 测试CDN URL（如果配置了CDN）
        if (!empty($this->config['cdn'])) {
            $this->assertStringContainsString($this->config['cdn'], $url, 'URL应该包含CDN域名');
        }
    }

    public function testTemporaryUrlGeneration()
    {
        $path = 'test/file.jpg';
        $expiration = now()->addHour();
        
        $temporaryUrl = $this->extendedAdapter->temporaryUrl($path, $expiration);
        $this->assertIsString($temporaryUrl, '临时URL应该是字符串');
    }

    public function testGetCOSClient()
    {
        $client = $this->extendedAdapter->getCOSClient();
        $this->assertInstanceOf(
            \Qcloud\Cos\Client::class,
            $client,
            'getCOSClient应该返回COS客户端实例'
        );
    }

    public function testWriteMethodSignature()
    {
        // 测试write方法的新签名（void返回类型）
        $reflection = new \ReflectionMethod($this->adapter, 'write');
        $returnType = $reflection->getReturnType();
        
        $this->assertNotNull($returnType, 'write方法应该有返回类型声明');
        $this->assertEquals('void', $returnType->getName(), 'write方法应该返回void');
    }

    public function testReadMethodSignature()
    {
        // 测试read方法的新签名（string返回类型）
        $reflection = new \ReflectionMethod($this->adapter, 'read');
        $returnType = $reflection->getReturnType();
        
        $this->assertNotNull($returnType, 'read方法应该有返回类型声明');
        $this->assertEquals('string', $returnType->getName(), 'read方法应该返回string');
    }

    public function testListContentsMethodSignature()
    {
        // 测试listContents方法的新签名（iterable返回类型）
        $reflection = new \ReflectionMethod($this->adapter, 'listContents');
        $returnType = $reflection->getReturnType();
        
        $this->assertNotNull($returnType, 'listContents方法应该有返回类型声明');
        $this->assertEquals('iterable', $returnType->getName(), 'listContents方法应该返回iterable');
    }

    public function testFileExistsMethodSignature()
    {
        // 测试fileExists方法的新签名（bool返回类型）
        $reflection = new \ReflectionMethod($this->adapter, 'fileExists');
        $returnType = $reflection->getReturnType();
        
        $this->assertNotNull($returnType, 'fileExists方法应该有返回类型声明');
        $this->assertEquals('bool', $returnType->getName(), 'fileExists方法应该返回bool');
    }

    public function testPutRemoteFileMethod()
    {
        // 测试putRemoteFile方法存在且可调用
        $this->assertTrue(
            method_exists($this->extendedAdapter, 'putRemoteFile'),
            'putRemoteFile方法应该存在'
        );
        
        $this->assertTrue(
            is_callable([$this->extendedAdapter, 'putRemoteFile']),
            'putRemoteFile方法应该可调用'
        );
    }

    public function testCloudInfiniteUrlMethod()
    {
        $path = 'test/image.jpg';
        $options = ['w' => 100, 'h' => 100];
        
        $url = $this->extendedAdapter->getCloudInfiniteUrl($path, $options);
        $this->assertIsString($url, 'Cloud Infinite URL应该是字符串');
        $this->assertStringContainsString('.pic.', $url, 'Cloud Infinite URL应该包含.pic.域名');
    }

    public function testAuthorizationMethod()
    {
        $method = 'GET';
        $url = 'https://example.com/test';
        
        $authorization = $this->extendedAdapter->getAuthorization($method, $url);
        $this->assertIsString($authorization, '授权字符串应该是字符串');
    }
}