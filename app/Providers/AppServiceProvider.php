<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL; // <-- IMPORTANTE: Agregar esto

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
        // Si estamos en producción, forzamos HTTPS
        if($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}