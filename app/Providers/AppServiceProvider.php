<?php

namespace App\Providers;

use App\Filament\Support\NotificarExcepcionesDeDominio;
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
        NotificarExcepcionesDeDominio::registrar();
    }
}
