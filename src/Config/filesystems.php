<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application. Just store away!
    |
    */

    'default' => 'local',

    /*
    |--------------------------------------------------------------------------
    | Default Cloud Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Many applications store files both locally and in the cloud. For this
    | reason, you may specify a default "cloud" driver here. This driver
    | will be bound as the Cloud disk implementation in the container.
    |
    */

    'cloud' => 's3',

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Here you may configure as many filesystem "disks" as you wish, and you
    | may even configure multiple disks of the same driver. Defaults have
    | been setup for each driver as an example of the required options.
    |
    | Supported Drivers: "local", "ftp", "s3", "rackspace"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root'   => storage_path('app'),
        ],

        'public' => [
            'driver'     => 'local',
            'root'       => storage_path('app/public'),
            'url'        => env('APP_URL').'/storage',
            'visibility' => 'public',
        ],

        's3' => [
            'driver' => 's3',
            'key'    => env('AWS_KEY'),
            'secret' => env('AWS_SECRET'),
            'region' => env('AWS_REGION'),
            'bucket' => env('AWS_BUCKET'),
        ],

        'qcloud-cos' => [
            'driver'         => 'qcloud-cos',
            'region'         => env('QCLOUD_COS_REGION', 'ap-guangzhou'),
            'credentials'    => [
                'appId'      => env('QCLOUD_COS_APP_ID'),
                'secretId'   => env('QCLOUD_COS_SECRET_ID'),
                'secretKey'  => env('QCLOUD_COS_SECRET_KEY'),
                'token'      => env('QCLOUD_COS_TOKEN'),
            ],
            'timeout'            => env('QCLOUD_COS_TIMEOUT', 60),
            'connect_timeout'    => env('QCLOUD_COS_CONNECT_TIMEOUT', 60),
            'bucket'             => env('QCLOUD_COS_BUCKET'),
            'cdn'                => env('QCLOUD_COS_CDN'),
            'scheme'             => env('QCLOUD_COS_SCHEME', 'https'),
            'read_from_cdn'      => env('QCLOUD_COS_READ_FROM_CDN', false),
            'cdn_key'            => env('QCLOUD_COS_CDN_KEY'),
            'encrypt'            => env('QCLOUD_COS_ENCRYPT', false),
        ],

    ],

];