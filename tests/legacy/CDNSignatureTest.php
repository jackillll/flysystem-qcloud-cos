<?php

namespace Freyo\Flysystem\QcloudCOS\Tests;

use Freyo\Flysystem\QcloudCOS\Adapter;
use Freyo\Flysystem\QcloudCOS\Plugins\CDN;
use League\Flysystem\Filesystem;
use PHPUnit\Framework\TestCase;
use Qcloud\Cos\Client;

class CDNSignatureTest extends TestCase
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
            'cdn_key'         => getenv('COS_CDN_KEY'),
        ];

        $client = new Client($config);

        $adapter = new Adapter($client, $config);

        $filesystem = new Filesystem($adapter, $config);

        $filesystem->addPlugin(new CDN());

        return [
            [$filesystem],
        ];
    }

    /**
     * @dataProvider Provider
     */
    public function testSignature(Filesystem $filesystem)
    {
        $this->assertSame(
            'http://www.test.com/1.mp4?sign=8fe5c42d7b0dfa7afabef2a33cd96459&t=5a66b340',
            $filesystem->cdn()->signature('http://www.test.com/1.mp4', null, 1516680000)
        );
    }

    /**
     * @dataProvider Provider
     */
    public function testSignatureA(Filesystem $filesystem)
    {
        $this->assertSame(
            'http://www.test.com/1.mp4?sign=1516680000-e9pmhkb21sjqfeh33f9-0-9a15f74f326dbb6dd485911eb0d9c629',
            $filesystem->cdn()->signatureA('http://www.test.com/1.mp4', null, 1516680000, 'e9pmhkb21sjqfeh33f9')
        );
    }

    /**
     * @dataProvider Provider
     */
    public function testSignatureB(Filesystem $filesystem)
    {
        date_default_timezone_set('UTC');

        $this->assertSame(
            'http://www.test.com/201801230400/8eee4e932f285743fa23c79030139459/1.mp4',
            $filesystem->cdn()->signatureB('http://www.test.com/1.mp4', null, 1516680000)
        );
    }

    /**
     * @dataProvider Provider
     */
    public function testSignatureC(Filesystem $filesystem)
    {
        $this->assertSame(
            'http://www.test.com/8fe5c42d7b0dfa7afabef2a33cd96459/5a66b340/1.mp4',
            $filesystem->cdn()->signatureC('http://www.test.com/1.mp4', null, 1516680000)
        );
    }

    /**
     * @dataProvider Provider
     */
    public function testSignatureD(Filesystem $filesystem)
    {
        $this->assertSame(
            'http://www.test.com/1.mp4?sign=8fe5c42d7b0dfa7afabef2a33cd96459&t=5a66b340',
            $filesystem->cdn()->signatureD('http://www.test.com/1.mp4', null, 1516680000)
        );
    }
}
