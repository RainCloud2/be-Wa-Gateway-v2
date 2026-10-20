<?php

namespace App\Providers;

use App\Services\Wa\GatewayDriver;
use App\Services\Wa\LogDriver;
use App\Services\Wa\WhatsAppDriver;
use Illuminate\Support\ServiceProvider;

class WaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(WhatsAppDriver::class, function () {
            return match (config('wa.driver')) {
                'gateway' => new GatewayDriver,
                default => new LogDriver,
            };
        });
    }

    public function boot(): void
    {
        //
    }
}
