<?php

namespace Jackillll\Flysystem\QcloudCos\Adapters;

use Carbon\Carbon;
use DateTimeInterface;
use League\Flysystem\Config;
use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\PathPrefixer;
use League\Flysystem\UnableToCheckDirectoryExistence;
use League\Flysystem\UnableToCheckFileExistence;
use League\Flysystem\UnableToCopyFile;
use League\Flysystem\UnableToCreateDirectory;
use League\Flysystem\UnableToDeleteDirectory;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToMoveFile;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToRetrieveMetadata;
use League\Flysystem\UnableToSetVisibility;
use League\Flysystem\UnableToWriteFile;
use League\Flysystem\Visibility;
use Qcloud\Cos\Client;
use Qcloud\Cos\Exception\ServiceResponseException;

/**
 * Tencent Cloud COS Adapter for Flysystem.
 */
class QcloudCosAdapter implements FilesystemAdapter
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * @var array
     */
    protected $config = [];

    /**
     * @var PathPrefixer
     */
    protected $prefixer;

    /**
     * @var array
     */
    protected $regionMap = [
        'cn-east'      => 'ap-shanghai',
        'cn-sorth'     => 'ap-guangzhou',
        'cn-north'     => 'ap-beijing-1',
        'cn-south-2'   => 'ap-guangzhou-2',
        'cn-southwest' => 'ap-chengdu',
        'sg'           => 'ap-singapore',
        'tj'           => 'ap-beijing-1',
        'bj'           => 'ap-beijing',
        'sh'           => 'ap-shanghai',
        'gz'           => 'ap-guangzhou',
        'cd'           => 'ap-chengdu',
        'sgp'          => 'ap-singapore',
    ];

    /**
     * Create a new QcloudCosAdapter instance.
     *
     * @param Client $client
     * @param array  $config
     */
    public function __construct(Client $client, array $config)
    {
        $this->client = $client;
        $this->config = $config;
        $this->prefixer = new PathPrefixer($config['cdn'] ?? '', DIRECTORY_SEPARATOR);
    }

    /**
     * Get bucket name with app ID.
     *
     * @return string
     */
    public function getBucketWithAppId(): string
    {
        return $this->getBucket().'-'.$this->getAppId();
    }

    /**
     * Get bucket name.
     *
     * @return string
     */
    public function getBucket(): string
    {
        return preg_replace(
            "/-{$this->getAppId()}$/",
            '',
            $this->config['bucket']
        );
    }

    /**
     * Get app ID.
     *
     * @return string
     */
    public function getAppId(): string
    {
        return $this->config['credentials']['appId'];
    }

    /**
     * Get region.
     *
     * @return string
     */
    public function getRegion(): string
    {
        return array_key_exists($this->config['region'], $this->regionMap)
            ? $this->regionMap[$this->config['region']] : $this->config['region'];
    }

    /**
     * Get source path.
     *
     * @param string $path
     * @return string
     */
    public function getSourcePath(string $path): string
    {
        return sprintf('%s/%s',
            $this->getBucketWithAppId(), $path
        );
    }

    /**
     * Get picture path for Cloud Infinite.
     *
     * @param string $path
     * @return string
     */
    public function getPicturePath(string $path): string
    {
        return sprintf('%s.pic.%s.myqcloud.com/%s',
            $this->getBucketWithAppId(), $this->getRegion(), $path
        );
    }

    /**
     * Get URL for the file.
     *
     * @param string $path
     * @return string
     */
    public function getUrl(string $path): string
    {
        if ($this->config['cdn']) {
            return $this->prefixer->prefixPath($path);
        }

        $options = [
            'Scheme' => isset($this->config['scheme']) ? $this->config['scheme'] : 'http',
        ];

        /** @var \GuzzleHttp\Psr7\Uri $objectUrl */
        $objectUrl = $this->client->getObjectUrl(
            $this->getBucketWithAppId(), $path, "+30 minutes", $options
        );

        return (string) $objectUrl;
    }

    /**
     * Get temporary URL for the file.
     *
     * @param string             $path
     * @param \DateTimeInterface $expiration
     * @param array              $options
     * @return string
     */
    public function getTemporaryUrl(string $path, DateTimeInterface $expiration, array $options = []): string
    {
        $options = array_merge(
            $options,
            ['Scheme' => isset($this->config['scheme']) ? $this->config['scheme'] : 'http']
        );

        /** @var \GuzzleHttp\Psr7\Uri $objectUrl */
        $objectUrl = $this->client->getObjectUrl(
            $this->getBucketWithAppId(), $path, $expiration->format('c'), $options
        );

        return (string) $objectUrl;
    }

    public function fileExists(string $path): bool
    {
        try {
            $this->client->headObject([
                'Bucket' => $this->getBucketWithAppId(),
                'Key'    => $path,
            ]);
            return true;
        } catch (ServiceResponseException $e) {
            if ($e->getStatusCode() === 404) {
                return false;
            }
            throw UnableToCheckFileExistence::forLocation($path, $e);
        }
    }

    public function directoryExists(string $path): bool
    {
        try {
            $response = $this->client->listObjects([
                'Bucket'  => $this->getBucketWithAppId(),
                'Prefix'  => rtrim($path, '/') . '/',
                'MaxKeys' => 1,
            ]);
            return !empty($response['Contents']);
        } catch (ServiceResponseException $e) {
            throw UnableToCheckDirectoryExistence::forLocation($path, $e);
        }
    }

    public function write(string $path, string $contents, Config $config): void
    {
        try {
            $this->client->upload(
                $this->getBucketWithAppId(),
                $path,
                $contents,
                $this->prepareUploadConfig($config)
            );
        } catch (ServiceResponseException $e) {
            throw UnableToWriteFile::atLocation($path, $e->getMessage(), $e);
        }
    }

    public function writeStream(string $path, $resource, Config $config): void
    {
        try {
            $this->client->upload(
                $this->getBucketWithAppId(),
                $path,
                stream_get_contents($resource, -1, 0),
                $this->prepareUploadConfig($config)
            );
        } catch (ServiceResponseException $e) {
            throw UnableToWriteFile::atLocation($path, $e->getMessage(), $e);
        }
    }

    public function read(string $path): string
    {
        try {
            $response = $this->forceReadFromCDN()
                ? $this->readFromCDN($path)
                : $this->readFromSource($path);

            return (string) $response;
        } catch (ServiceResponseException $e) {
            throw UnableToReadFile::fromLocation($path, $e->getMessage(), $e);
        }
    }

    public function readStream(string $path)
    {
        try {
            $temporaryUrl = $this->getTemporaryUrl($path, Carbon::now()->addMinutes(5));

            $stream = $this->getHttpClient()
                           ->get($temporaryUrl, ['stream' => true])
                           ->getBody()
                           ->detach();

            return $stream;
        } catch (ServiceResponseException $e) {
            throw UnableToReadFile::fromLocation($path, $e->getMessage(), $e);
        }
    }

    public function delete(string $path): void
    {
        try {
            $this->client->deleteObject([
                'Bucket' => $this->getBucketWithAppId(),
                'Key'    => $path,
            ]);
        } catch (ServiceResponseException $e) {
            throw UnableToDeleteFile::atLocation($path, $e->getMessage(), $e);
        }
    }

    public function deleteDirectory(string $path): void
    {
        try {
            $this->client->deleteObject([
                'Bucket' => $this->getBucketWithAppId(),
                'Key'    => rtrim($path, '/') . '/',
            ]);
        } catch (ServiceResponseException $e) {
            throw UnableToDeleteDirectory::atLocation($path, $e->getMessage(), $e);
        }
    }

    public function createDirectory(string $path, Config $config): void
    {
        try {
            $this->client->putObject([
                'Bucket' => $this->getBucketWithAppId(),
                'Key'    => rtrim($path, '/') . '/',
                'Body'   => '',
            ]);
        } catch (ServiceResponseException $e) {
            throw UnableToCreateDirectory::atLocation($path, $e->getMessage(), $e);
        }
    }

    public function setVisibility(string $path, string $visibility): void
    {
        try {
            $this->client->putObjectAcl([
                'Bucket' => $this->getBucketWithAppId(),
                'Key'    => $path,
                'ACL'    => $this->normalizeVisibility($visibility),
            ]);
        } catch (ServiceResponseException $e) {
            throw UnableToSetVisibility::atLocation($path, $e->getMessage(), $e);
        }
    }

    public function visibility(string $path): FileAttributes
    {
        try {
            $meta = $this->client->getObjectAcl([
                'Bucket' => $this->getBucketWithAppId(),
                'Key'    => $path,
            ]);

            foreach ($meta['Grants'] as $grant) {
                if (isset($grant['Grantee']['URI'])
                    && $grant['Permission'] === 'READ'
                    && strpos($grant['Grantee']['URI'], 'global/AllUsers') !== false
                ) {
                    return new FileAttributes($path, null, Visibility::PUBLIC);
                }
            }

            return new FileAttributes($path, null, Visibility::PRIVATE);
        } catch (ServiceResponseException $e) {
            throw UnableToRetrieveMetadata::visibility($path, $e->getMessage(), $e);
        }
    }

    public function mimeType(string $path): FileAttributes
    {
        try {
            $meta = $this->client->headObject([
                'Bucket' => $this->getBucketWithAppId(),
                'Key'    => $path,
            ]);

            return new FileAttributes($path, null, null, null, $meta['ContentType'] ?? null);
        } catch (ServiceResponseException $e) {
            throw UnableToRetrieveMetadata::mimeType($path, $e->getMessage(), $e);
        }
    }

    public function lastModified(string $path): FileAttributes
    {
        try {
            $meta = $this->client->headObject([
                'Bucket' => $this->getBucketWithAppId(),
                'Key'    => $path,
            ]);

            $timestamp = isset($meta['LastModified']) ? strtotime($meta['LastModified']) : null;

            return new FileAttributes($path, null, null, $timestamp);
        } catch (ServiceResponseException $e) {
            throw UnableToRetrieveMetadata::lastModified($path, $e->getMessage(), $e);
        }
    }

    public function fileSize(string $path): FileAttributes
    {
        try {
            $meta = $this->client->headObject([
                'Bucket' => $this->getBucketWithAppId(),
                'Key'    => $path,
            ]);

            return new FileAttributes($path, $meta['ContentLength'] ?? null);
        } catch (ServiceResponseException $e) {
            throw UnableToRetrieveMetadata::fileSize($path, $e->getMessage(), $e);
        }
    }

    public function listContents(string $path, bool $deep): iterable
    {
        $marker = '';
        
        while (true) {
            try {
                $response = $this->client->listObjects([
                    'Bucket'    => $this->getBucketWithAppId(),
                    'Prefix'    => $path === '' ? '' : rtrim($path, '/') . '/',
                    'Delimiter' => $deep ? '' : '/',
                    'Marker'    => $marker,
                    'MaxKeys'   => 1000,
                ]);

                foreach ((array) ($response['Contents'] ?? []) as $content) {
                    yield $this->normalizeFileInfo($content);
                }

                foreach ((array) ($response['CommonPrefixes'] ?? []) as $prefix) {
                    yield new DirectoryAttributes(rtrim($prefix['Prefix'], '/'));
                }

                if (!($response['IsTruncated'] ?? false)) {
                    break;
                }
                
                $marker = $response['NextMarker'] ?? '';
            } catch (ServiceResponseException $e) {
                break;
            }
        }
    }

    public function move(string $source, string $destination, Config $config): void
    {
        try {
            // 先复制文件
            $this->copy($source, $destination, $config);
            
            // 只有复制成功后才删除源文件
            try {
                $this->delete($source);
            } catch (ServiceResponseException $deleteException) {
                // 如果删除失败，记录错误但不影响移动操作的成功状态
                // 因为文件已经成功复制到目标位置
                error_log('Warning: Failed to delete source file after successful copy: ' . $deleteException->getMessage());
            }
        } catch (ServiceResponseException $e) {
            throw UnableToMoveFile::fromLocationTo($source, $destination, $e);
        }
    }

    public function copy(string $source, string $destination, Config $config): void
    {
        try {
            $this->client->copyObject([
                'Bucket'     => $this->getBucketWithAppId(),
                'Key'        => $destination,
                'CopySource' => $this->getSourcePath($source),
            ]);
        } catch (ServiceResponseException $e) {
            throw UnableToCopyFile::fromLocationTo($source, $destination, $e);
        }
    }

    /**
     * Check if should read from CDN.
     *
     * @return bool
     */
    protected function forceReadFromCDN(): bool
    {
        return $this->config['cdn']
            && isset($this->config['read_from_cdn'])
            && $this->config['read_from_cdn'];
    }

    /**
     * Read file from CDN.
     *
     * @param string $path
     * @return string
     */
    protected function readFromCDN(string $path): string
    {
        return $this->getHttpClient()
            ->get($this->prefixer->prefixPath($path))
            ->getBody()
            ->getContents();
    }

    /**
     * Read file from source.
     *
     * @param string $path
     * @return string
     */
    protected function readFromSource(string $path): string
    {
        try {
            $response = $this->client->getObject([
                'Bucket' => $this->getBucketWithAppId(),
                'Key'    => $path,
            ]);

            return $response['Body'];
        } catch (ServiceResponseException $e) {
            return false;
        }
    }

    /**
     * Get HTTP client.
     *
     * @return \GuzzleHttp\Client
     */
    public function getHttpClient(): \GuzzleHttp\Client
    {
        return new \GuzzleHttp\Client([
            'timeout'         => $this->config['timeout'],
            'connect_timeout' => $this->config['connect_timeout'],
        ]);
    }

    /**
     * Normalize file info.
     *
     * @param array $content
     * @return FileAttributes
     */
    private function normalizeFileInfo(array $content): FileAttributes
    {
        $path = $content['Key'];
        $lastModified = isset($content['LastModified']) ? strtotime($content['LastModified']) : null;
        $fileSize = isset($content['Size']) ? (int) $content['Size'] : null;

        return new FileAttributes($path, $fileSize, null, $lastModified);
    }

    /**
     * Prepare upload config.
     *
     * @param Config $config
     * @return array
     */
    private function prepareUploadConfig(Config $config): array
    {
        $options = [];

        if (isset($this->config['encrypt']) && $this->config['encrypt']) {
            $options['ServerSideEncryption'] = 'AES256';
        }

        if ($config->get('params') !== null) {
            $options = array_merge($options, $config->get('params'));
        }

        if ($config->get('visibility') !== null) {
            $options['ACL'] = $this->normalizeVisibility($config->get('visibility'));
        }

        return $options;
    }

    /**
     * Normalize visibility.
     *
     * @param string $visibility
     * @return string
     */
    private function normalizeVisibility(string $visibility): string
    {
        switch ($visibility) {
            case Visibility::PUBLIC:
                $visibility = 'public-read';
                break;
        }

        return $visibility;
    }

    /**
     * Get COS client.
     *
     * @return Client
     */
    public function getCOSClient(): Client
    {
        return $this->client;
    }

    /**
     * Get authorization.
     *
     * @param string $method
     * @param string $url
     * @return string
     */
    public function getAuthorization(string $method, string $url): string
    {
        $cosRequest = new \GuzzleHttp\Psr7\Request($method, $url);

        $signature = new \Qcloud\Cos\Signature(
            $this->config['credentials']['secretId'],
            $this->config['credentials']['secretKey'],
            time(),
            time() + 3600
        );

        return $signature->createAuthorization($cosRequest);
    }
}