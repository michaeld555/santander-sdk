<?php

declare(strict_types=1);

namespace Santander\SDK;

use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\ServiceProvider;
use Santander\SDK\Auth\SantanderAuth;
use Santander\SDK\Client\SantanderApiClient;
use Santander\SDK\Client\SantanderClientConfiguration;

class SantanderServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/santander.php', 'santander');

        $this->app->singleton(SantanderClientConfiguration::class, function ($app) {
            $config = $app['config']->get('santander', []);
            return SantanderClientConfiguration::fromArray($config);
        });

        $this->app->singleton(SantanderAuth::class, function ($app) {
            $config = $app->make(SantanderClientConfiguration::class);
            return SantanderAuth::fromConfig($app->make(HttpFactory::class), $config);
        });

        $this->app->singleton(SantanderApiClient::class, function ($app) {
            $config = $app->make(SantanderClientConfiguration::class);
            $auth = $app->make(SantanderAuth::class);
            return new SantanderApiClient($config, $auth, $app->make(HttpFactory::class));
        });

        $this->app->singleton(Pix::class, function ($app) {
            return new Pix($app->make(SantanderApiClient::class));
        });

        $this->app->singleton(PaymentReceipts::class, function ($app) {
            return new PaymentReceipts($app->make(SantanderApiClient::class));
        });

        $this->app->singleton(SantanderSdk::class, function ($app) {
            return new SantanderSdk(
                $app->make(SantanderApiClient::class),
                $app->make(Pix::class),
                $app->make(PaymentReceipts::class)
            );
        });
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/santander.php' => config_path('santander.php'),
        ], 'santander-config');
    }
}