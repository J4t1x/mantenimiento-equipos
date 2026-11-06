<?php

namespace App\Filament\Pages;

use App\Support\GuiaDeUsuario as Guia;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

/**
 * SPEC-ME-017: guía de usuario dentro del sistema, para todos los perfiles. Muestra
 * `docs/MANUAL-USUARIO.md` ({@see Guia}) y destaca la ruta de capacitación del perfil del usuario.
 * Se abre desde el Escritorio, el menú lateral y el menú de usuario.
 */
class GuiaDeUsuario extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static ?string $navigationLabel = 'Guía de usuario';

    protected static ?string $title = 'Guía de usuario';

    protected static ?string $slug = 'guia-de-usuario';

    protected static ?int $navigationSort = 100;

    protected string $view = 'filament.pages.guia-de-usuario';

    public static function canAccess(): bool
    {
        return auth()->check();
    }

    public function getSubheading(): ?string
    {
        return 'Cómo usar el sistema paso a paso, con la ruta de capacitación de cada perfil.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('imprimir')
                ->label('Imprimir o guardar como PDF')
                ->icon(Heroicon::OutlinedPrinter)
                ->color('gray')
                ->alpineClickHandler('window.print()'),
        ];
    }

    /**
     * @return array{html: string, rutas: array<int, array{perfil: string, ancla: string}>}
     */
    protected function getViewData(): array
    {
        return [
            'html' => Guia::html(),
            'rutas' => Guia::rutasDe(auth()->user()),
        ];
    }
}
