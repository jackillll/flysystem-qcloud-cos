<?php

namespace Jackillll\Flysystem\QcloudCos\Providers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use Jackillll\Flysystem\QcloudCos\Adapters\QcloudCosAdapter;
use Jackillll\Flysystem\QcloudCos\Adapters\QcloudCosFilesystemAdapter;
use Laravel\Lumen\Application as LumenApplication;
use League\Flysystem\Filesystem;
use Qcloud\Cos\Client;

/**
 * Tencent Cloud COS Service Provider.
 */
class QcloudCosServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app instanceof LumenApplication) {
            $this->app->configure('filesystems');
        }

        // Register qcloud-cos driver
        $driverCallback = function (Application $app, array $config) {
            $client = new Client($config);
            $adapter = new QcloudCosAdapter($client, $config);
            
            return new QcloudCosFilesystemAdapter(
                new Filesystem($adapter, $config),
                $adapter,
                $config
            );
        };
        
        Storage::extend('qcloud-cos', $driverCallback);
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../Config/filesystems.php', 'filesystems'
        );
    }
}