<?php

namespace App\Providers;

use App\Filament\Support\NotificarExcepcionesDeDominio;
use App\Listeners\RegistrarEventosAutenticacion;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

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

        // SPEC-ME-019: una sola política de contraseñas (formulario de Usuarios, recuperación por
        // correo y app:crear-usuario).
        Password::defaults(fn (): Password => Password::min((int) config('seguridad.contrasena.largo_minimo')));

        // SPEC-ME-019 (RF-89): registro de eventos de autenticación.
        Event::subscribe(RegistrarEventosAutenticacion::class);
    }
}
