<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Auth;
use App\Auth\TokenGuard; // Ensure this path is correct

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
        Auth::extend('token', function ($app, $name, array $config) {
            $provider = Auth::createUserProvider($config['provider']); // Ensure this is correct
            if (!$provider) {
                throw new \Exception("Provider not found for guard: $name");
            }
            return new TokenGuard($provider, $app['request']);
        });
        
    }
}
