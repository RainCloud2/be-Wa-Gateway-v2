<?php

namespace App\Providers;

use App\Services\Wa\QueueWorkerProcessManager;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::before(function ($user, $ability) {
            return $user->hasRole('super-admin') ? true : null;
        });

        Paginator::useBootstrapFive();

        // Rate limit the gateway -> Laravel endpoints by source IP.
        RateLimiter::for('wa', function (Request $request) {
            return Limit::perMinute(120)->by($request->ip());
        });

        Queue::starting(function () {
            $this->writeQueueHeartbeat();
        });

        Queue::looping(function () {
            $last = Cache::get(QueueWorkerProcessManager::HEARTBEAT_KEY);

            if (! $last || Carbon::parse($last)->lt(now()->subSeconds(10))) {
                $this->writeQueueHeartbeat();
            }
        });

        Queue::stopping(function () {
            Cache::forget(QueueWorkerProcessManager::HEARTBEAT_KEY);
        });
    }

    private function writeQueueHeartbeat(): void
    {
        Cache::put(
            QueueWorkerProcessManager::HEARTBEAT_KEY,
            now()->toIso8601String(),
            now()->addMinutes(5),
        );
    }
}
