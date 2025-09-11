<?php

namespace Jackillll\Flysystem\QcloudCos;

use Qcloud\Cos\Client;
use League\Flysystem\Filesystem;
use Jackillll\Flysystem\QcloudCos\Adapters\QcloudCosAdapter;
use Jackillll\Flysystem\QcloudCos\Adapters\QcloudCosFilesystemAdapter;

/**
 * Factory class for creating QcloudCos instances in standalone PHP projects.
 */
class QcloudCosFactory
{
    /**
     * Create a basic Flysystem instance with QcloudCos adapter.
     *
     * @param array $config
     * @return Filesystem
     */
    public static function createFilesystem(array $config): Filesystem
    {
        $client = new Client($config);
        $adapter = new QcloudCosAdapter($client, $config);
        
        return new Filesystem($adapter, $config);
    }

    /**
     * Create an extended filesystem adapter with additional features.
     *
     * @param array $config
     * @return QcloudCosFilesystemAdapter
     */
    public static function createExtendedAdapter(array $config): QcloudCosFilesystemAdapter
    {
        $client = new Client($config);
        $adapter = new QcloudCosAdapter($client, $config);
        $filesystem = new Filesystem($adapter, $config);
        
        return new QcloudCosFilesystemAdapter($filesystem, $adapter, $config);
    }

    /**
     * Create a COS client instance.
     *
     * @param array $config
     * @return Client
     */
    public static function createClient(array $config): Client
    {
        return new Client($config);
    }

    /**
     * Create a basic adapter instance.
     *
     * @param array $config
     * @return QcloudCosAdapter
     */
    public static function createAdapter(array $config): QcloudCosAdapter
    {
        $client = new Client($config);
        
        return new QcloudCosAdapter($client, $config);
    }

    /**
     * Validate configuration array.
     *
     * @param array $config
     * @return bool
     * @throws \InvalidArgumentException
     */
    public static function validateConfig(array $config): bool
    {
        $required = ['region', 'credentials', 'bucket'];
        
        foreach ($required as $key) {
            if (!isset($config[$key])) {
                throw new \InvalidArgumentException("Missing required config key: {$key}");
            }
        }
        
        $credentials = $config['credentials'];
        $requiredCredentials = ['appId', 'secretId', 'secretKey'];
        
        foreach ($requiredCredentials as $key) {
            if (!isset($credentials[$key])) {
                throw new \InvalidArgumentException("Missing required credential: {$key}");
            }
        }
        
        return true;
    }

    /**
     * Get default configuration array.
     *
     * @return array
     */
    public static function getDefaultConfig(): array
    {
        return [
            'region' => 'ap-guangzhou',
            'credentials' => [
                'appId' => '',
                'secretId' => '',
                'secretKey' => '',
                'token' => null,
            ],
            'timeout' => 60,
            'connect_timeout' => 60,
            'bucket' => '',
            'cdn' => '',
            'scheme' => 'https',
            'read_from_cdn' => false,
            'cdn_key' => '',
            'encrypt' => false,
        ];
    }

    /**
     * Merge user config with default config.
     *
     * @param array $config
     * @return array
     */
    public static function mergeConfig(array $config): array
    {
        return array_merge_recursive(self::getDefaultConfig(), $config);
    }
}