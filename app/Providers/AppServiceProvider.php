<?php

namespace App\Providers;

use App\Auth\JWTGuard;
use App\Services\JWTService;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Auth::extend(
            'jwt',
            function (Application $app, string $name, array $config) {
                return new JWTGuard(
                    jwtService: $app->make(JWTService::class),
                    provider: $app['auth']->createUserProvider($config['provider']),
                    request: $app['request'],
                );
            }
        );
    }
}
