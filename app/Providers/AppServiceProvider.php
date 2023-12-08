<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repositories\CacheRepository;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(CacheRepository::class, function ($app) {
            return new CacheRepository();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
