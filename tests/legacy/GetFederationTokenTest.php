<?php

namespace Freyo\Flysystem\QcloudCOS\Tests;

use Freyo\Flysystem\QcloudCOS\Adapter;
use Freyo\Flysystem\QcloudCOS\Plugins\GetFederationToken;
use League\Flysystem\Filesystem;
use PHPUnit\Framework\TestCase;
use Qcloud\Cos\Client;

class GetFederationTokenTest extends TestCase
{
    public function Provider()
    {
        $config = [
            'region'      => getenv('COS_REGION'),
        'credentials' => [
            'appId'     => getenv('COS_APP_ID'),
            'secretId'  => getenv('COS_SECRET_ID'),
            'secretKey' => getenv('COS_SECRET_KEY'),
        ],
        'timeout'         => getenv('COS_TIMEOUT'),
        'connect_timeout' => getenv('COS_CONNECT_TIMEOUT'),
        'bucket'          => getenv('COS_BUCKET'),
        'cdn'             => getenv('COS_CDN'),
        'scheme'          => getenv('COS_SCHEME'),
        'read_from_cdn'   => getenv('COS_READ_FROM_CDN'),
        ];

        $client = new Client($config);

        $adapter = new Adapter($client, $config);

        $filesystem = new Filesystem($adapter, $config);

        $filesystem->addPlugin(new GetFederationToken());

        return [
            [$filesystem],
        ];
    }

    /**
     * @dataProvider Provider
     */
    public function testDefault(Filesystem $filesystem)
    {
        $this->assertArrayHasKey('credentials', $filesystem->getFederationToken());
    }

    /**
     * @dataProvider Provider
     */
    public function testCustom(Filesystem $filesystem)
    {
        $this->assertArrayHasKey(
            'credentials',
            $filesystem->getFederationToken('custom/path/to', 7200, function ($path, $config) {
                $appId = $config->get('credentials')['appId'];
                $region = $config->get('region');
                $bucket = $config->get('bucket');

                return [
                    'version'   => '2.0',
                    'statement' => [
                        'action' => [
                            'name/cos:PutObject',
                        ],
                        'effect'    => 'allow',
                        'principal' => ['qcs' => ['*']],
                        'resource'  => [
                            "qcs::cos:$region:uid/$appId:prefix//$appId/$bucket/$path",
                        ],
                    ],
                ];
            })
        );
    }
}
