<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Escritorio;
use App\Filament\Widgets\AlertasConveniosWidget;
use App\Filament\Widgets\CatastroAlertasWidget;
use App\Filament\Widgets\ConveniosEncargadoWidget;
use App\Filament\Widgets\CumplimientoMpMensualWidget;
use App\Filament\Widgets\CumplimientoMpWidget;
use App\Filament\Widgets\DistribucionCatastroCriticidadWidget;
use App\Filament\Widgets\DistribucionCatastroWidget;
use App\Filament\Widgets\GastoMensualWidget;
use App\Filament\Widgets\IndicadoresGeneralesWidget;
use App\Filament\Widgets\MisPlanesDelMesWidget;
use App\Http\Controllers\ImprimirInformeCriticosController;
use App\Http\Controllers\ImprimirInformeCumplimientoController;
use App\Support\PaletaInstitucional;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    /**
     * Fechas en formato DD-MM-YYYY (RNF-10 del SRS): se configura una sola vez para todos los
     * campos de fecha del panel, en vez de repetirlo en cada formulario.
     */
    public function boot(): void
    {
        DatePicker::configureUsing(fn (DatePicker $component) => $component
            ->native(false)
            ->displayFormat('d-m-Y'));

        DateTimePicker::configureUsing(fn (DateTimePicker $component) => $component
            ->native(false)
            ->displayFormat('d-m-Y H:i'));
    }

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandName('Bitácora de Mantenimiento de Equipos')
            ->brandLogo(asset('images/logo-ssa.png'))
            ->brandLogoHeight('2.5rem')
            ->favicon(asset('images/logo-ssa.png'))
            // Paleta institucional (Gobierno de Chile / Servicio de Salud Aysén, mismos valores del
            // mockup aprobado): azul y rojo del logo, grises azulados en vez de neutros, e "info" en
            // celeste para que no se confunda con el azul principal (lo usan "Programado" e "IM ≥ 12").
            // `PaletaInstitucional` deja el tono 600 en el color exacto (ver su docblock). Los
            // gráficos usan los mismos tonos en hex (`App\Support\PaletaGraficos`).
            ->colors([
                'primary' => PaletaInstitucional::escala('#0060AF'),
                'danger' => PaletaInstitucional::escala('#E2001A'),
                'gray' => Color::Slate,
                'info' => Color::Sky,
                'success' => Color::Emerald,
                'warning' => Color::Amber,
            ])
            ->databaseNotifications()
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Escritorio::class,
            ])
            // RF-77: informe de críticos imprimible, con la autenticación y el middleware del panel.
            ->authenticatedRoutes(function (): void {
                Route::get('/reportes/informe-criticos/imprimir', ImprimirInformeCriticosController::class)
                    ->name('informe-criticos.imprimir');
                // RF-78: informe trimestral / anual de cumplimiento y gasto, imprimible.
                Route::get('/reportes/informe-cumplimiento/imprimir', ImprimirInformeCumplimientoController::class)
                    ->name('informe-cumplimiento.imprimir');
            })
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                IndicadoresGeneralesWidget::class,
                CumplimientoMpWidget::class,
                AlertasConveniosWidget::class,
                ConveniosEncargadoWidget::class,
                MisPlanesDelMesWidget::class,
                CatastroAlertasWidget::class,
                CumplimientoMpMensualWidget::class,
                GastoMensualWidget::class,
                DistribucionCatastroWidget::class,
                DistribucionCatastroCriticidadWidget::class,
                AccountWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->plugins([
                FilamentShieldPlugin::make(),
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
