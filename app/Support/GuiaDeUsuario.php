<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension;

/**
 * SPEC-ME-017: la guía de usuario del panel se genera desde `docs/MANUAL-USUARIO.md`, la misma
 * fuente que se publica en el repositorio, para que no existan dos versiones del manual.
 *
 * Los títulos de nivel 2 y 3 reciben un `id` con el mismo formato que usa GitHub, así que los
 * enlaces del índice funcionan igual en el repositorio y en el sistema. Las imágenes
 * (`manual/img/…`) se sirven por la ruta autenticada `guia.imagen`.
 */
final class GuiaDeUsuario
{
    public const ARCHIVO = 'docs/MANUAL-USUARIO.md';

    public const PATRON_IMAGEN = '[A-Za-z0-9\-]+\.png';

    /**
     * Ancla de la ruta de capacitación de cada perfil, en el orden en que se muestran.
     */
    public const RUTAS_POR_PERFIL = [
        'Técnico Interno' => 'ruta-técnico-interno',
        'Encargado de Mantención' => 'ruta-encargado-de-mantención',
        'Encargado de Convenios' => 'ruta-encargado-de-convenios',
        'Jefatura' => 'ruta-jefatura',
        'super_admin' => 'ruta-administrador',
    ];

    public const NOMBRES_DE_PERFIL = [
        'super_admin' => 'Administrador',
    ];

    public static function rutaArchivo(): string
    {
        return base_path(self::ARCHIVO);
    }

    public static function directorioImagenes(): string
    {
        return base_path('docs/manual/img');
    }

    /**
     * HTML del manual. Se guarda en caché mientras el archivo no cambie.
     */
    public static function html(): string
    {
        $archivo = self::rutaArchivo();

        return Cache::rememberForever('guia-usuario:'.filemtime($archivo), function () use ($archivo): string {
            $html = Str::markdown(
                file_get_contents($archivo),
                [
                    'html_input' => 'strip',
                    'allow_unsafe_links' => false,
                    'heading_permalink' => [
                        'html_class' => 'guia-ancla',
                        'id_prefix' => '',
                        'fragment_prefix' => '',
                        'apply_id_to_heading' => true,
                        'heading_class' => 'guia-titulo',
                        'insert' => 'after',
                        'min_heading_level' => 2,
                        'max_heading_level' => 3,
                        'symbol' => '#',
                        'aria_hidden' => true,
                        'title' => 'Enlace a esta sección',
                    ],
                ],
                [new HeadingPermalinkExtension],
            );

            return preg_replace_callback(
                '/src="manual\/img\/('.self::PATRON_IMAGEN.')"/',
                fn (array $coincidencia): string => 'src="'.e(route('filament.admin.guia.imagen', ['archivo' => $coincidencia[1]])).'" loading="lazy"',
                $html,
            );
        });
    }

    /**
     * Perfiles del usuario con el ancla de su ruta de capacitación.
     *
     * @return array<int, array{perfil: string, ancla: string}>
     */
    public static function rutasDe(?User $usuario): array
    {
        if ($usuario === null) {
            return [];
        }

        $roles = $usuario->getRoleNames();

        return collect(self::RUTAS_POR_PERFIL)
            ->filter(fn (string $ancla, string $rol): bool => $roles->contains($rol))
            ->map(fn (string $ancla, string $rol): array => ['perfil' => self::NOMBRES_DE_PERFIL[$rol] ?? $rol, 'ancla' => $ancla])
            ->values()
            ->all();
    }
}
