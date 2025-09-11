# 升级指南：Laravel 12 兼容性

本文档描述了如何将 `jackillll/filesystem-qcloud-cos` 从 v2.x 升级到 v3.x 以支持 Laravel 12。

## 主要变更

### 1. 系统要求

- **PHP**: 从 `>=5.5.0` 升级到 `^8.2`
- **Laravel**: 支持 Laravel 12.x
- **Flysystem**: 从 `^1.0 || ^2.0 || ^3.0` 升级到 `^3.0`
- **Carbon**: 从 `~1.0 || ^2.0` 升级到 `^3.0`

### 2. 插件系统变更

**v2.x (旧版本)**:
```php
// 插件系统已被移除
$flysystem->addPlugin(new PutRemoteFile());
$flysystem->addPlugin(new GetUrl());
```

**v3.x (新版本)**:
```php
// 功能已集成到扩展适配器中
$disk = Storage::disk('cosv5');
$url = $disk->url('path/to/file');
$disk->putRemoteFile('path', 'http://example.com/file.jpg');
```

### 3. API 变更

#### 文件系统适配器

**v2.x**:
```php
use League\Flysystem\Adapter\AbstractAdapter;

class Adapter extends AbstractAdapter implements CanOverwriteFiles
{
    public function write($path, $contents, Config $config)
    {
        // 返回 array|false
    }
}
```

**v3.x**:
```php
use League\Flysystem\FilesystemAdapter;

class Adapter implements FilesystemAdapter
{
    public function write(string $path, string $contents, Config $config): void
    {
        // 返回 void，抛出异常表示失败
    }
}
```

#### 服务提供者注册

**v2.x**:
```php
$this->app->make('filesystem')
          ->extend('cosv5', function ($app, $config) {
              $client = new Client($config);
              $flysystem = new Filesystem(new Adapter($client, $config), $config);
              
              // 添加插件
              $flysystem->addPlugin(new GetUrl());
              
              return $flysystem;
          });
```

**v3.x**:
```php
Storage::extend('cosv5', function (Application $app, array $config) {
    $client = new Client($config);
    $adapter = new Adapter($client, $config);
    
    return new QcloudCOSv5FilesystemAdapter(
        new Filesystem($adapter, $config),
        $adapter,
        $config
    );
});
```

## 升级步骤

### 1. 更新 Composer 依赖

```bash
composer require "jackillll/filesystem-qcloud-cos:^3.0"
```

### 2. 更新 PHP 版本

确保您的项目使用 PHP 8.2 或更高版本。

### 3. 更新代码

#### 替换插件调用

**旧代码**:
```php
$disk = Storage::disk('cosv5');

// 这些方法在 v3.x 中不再可用
$url = $disk->getUrl('path/to/file');
$disk->putRemoteFile('path', 'http://example.com/file.jpg');
```

**新代码**:
```php
$disk = Storage::disk('cosv5');

// 使用新的方法
$url = $disk->url('path/to/file');
$disk->putRemoteFile('path', 'http://example.com/file.jpg');
```

#### 可用的新方法

```php
$disk = Storage::disk('cosv5');

// 基本文件操作（继承自 Laravel FilesystemAdapter）
$disk->put('path', 'contents');
$disk->get('path');
$disk->delete('path');
$disk->exists('path');

// COS 特定功能
$disk->url('path');                                    // 获取文件 URL
$disk->temporaryUrl('path', now()->addHour());        // 获取临时 URL
$disk->putRemoteFile('path', 'http://example.com/file.jpg'); // 上传远程文件
$disk->putRemoteFileAs('http://example.com/file.jpg'); // 上传远程文件并自动命名
$disk->getCdnUrl('path');                             // 获取 CDN URL
$disk->getCloudInfiniteUrl('path', ['w' => 100]);     // 获取图片处理 URL
$disk->getCOSClient();                                // 获取原始 COS 客户端
```

### 4. 测试升级

运行提供的测试脚本来验证升级：

```bash
php test_upgrade.php
```

## 配置变更

配置文件 `config/filesystems.php` 中的 `cosv5` 配置保持不变：

```php
'qcloud-cos' => [
    'driver'         => 'qcloud-cos',
    'region'         => env('COSV5_REGION', 'ap-guangzhou'),
    'credentials'    => [
        'appId'      => env('COSV5_APP_ID'),
        'secretId'   => env('COSV5_SECRET_ID'),
        'secretKey'  => env('COSV5_SECRET_KEY'),
        'token'      => env('COSV5_TOKEN'),
    ],
    'timeout'            => env('COSV5_TIMEOUT', 60),
    'connect_timeout'    => env('COSV5_CONNECT_TIMEOUT', 60),
    'bucket'             => env('COSV5_BUCKET'),
    'cdn'                => env('COSV5_CDN'),
    'scheme'             => env('COSV5_SCHEME', 'https'),
    'read_from_cdn'      => env('COSV5_READ_FROM_CDN', false),
    'cdn_key'            => env('COSV5_CDN_KEY'),
    'encrypt'            => env('COSV5_ENCRYPT', false),
],
```

## 故障排除

### 常见问题

1. **方法不存在错误**
   - 确保使用新的方法名称
   - 检查是否正确升级到 v3.x

2. **类型错误**
   - PHP 8.2+ 有更严格的类型检查
   - 确保传递正确的参数类型

3. **依赖冲突**
   - 运行 `composer update` 更新所有依赖
   - 检查其他包是否与 Flysystem 3.x 兼容

### 获取帮助

如果遇到问题，请：

1. 检查 [GitHub Issues](https://github.com/freyo/flysystem-qcloud-cos-v5/issues)
2. 提交新的 Issue 并包含详细的错误信息
3. 确保提供 PHP 版本、Laravel 版本和错误堆栈跟踪

## 回滚

如果需要回滚到旧版本：

```bash
composer require "jackillll/filesystem-qcloud-cos:^2.0"
```

注意：回滚后需要恢复旧的插件调用方式。