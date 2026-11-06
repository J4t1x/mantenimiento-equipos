<?php

namespace Tests\Feature\Panel;

use App\Filament\Pages\Escritorio;
use App\Filament\Pages\GuiaDeUsuario;
use App\Support\GuiaDeUsuario as Guia;
use Filament\Facades\Filament;
use Filament\Navigation\NavigationItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * SPEC-ME-017: guía de usuario dentro del sistema, generada desde docs/MANUAL-USUARIO.md.
 */
class GuiaDeUsuarioTest extends TestCase
{
    use RefreshDatabase;

    private function urlImagen(string $archivo): string
    {
        return route('filament.admin.guia.imagen', ['archivo' => $archivo]);
    }

    public function test_todos_los_perfiles_abren_la_guia_y_ven_su_ruta_de_capacitacion(): void
    {
        foreach (array_keys(Guia::RUTAS_POR_PERFIL) as $rol) {
            $this->actuarComo($rol);
            $ancla = Guia::RUTAS_POR_PERFIL[$rol];

            $this->get(GuiaDeUsuario::getUrl())
                ->assertOk()
                ->assertSee('Su perfil: '.(Guia::NOMBRES_DE_PERFIL[$rol] ?? $rol), false)
                ->assertSee('href="#'.$ancla.'"', false)
                ->assertSee('id="'.$ancla.'"', false)
                ->assertSee('Imprimir o guardar como PDF');
        }
    }

    public function test_sin_sesion_no_se_puede_abrir_la_guia_ni_sus_imagenes(): void
    {
        $this->get(GuiaDeUsuario::getUrl())->assertRedirect();
        $this->get($this->urlImagen('01-ingreso.png'))->assertRedirect();
    }

    public function test_la_guia_se_abre_desde_el_escritorio_y_el_menu_de_usuario(): void
    {
        $this->actuarComo('Técnico Interno');

        Livewire::test(Escritorio::class)->assertActionHasUrl('guiaDeUsuario', GuiaDeUsuario::getUrl());

        $this->get(Escritorio::getUrl())->assertOk()->assertSee(GuiaDeUsuario::getUrl(), false);
    }

    public function test_los_enlaces_del_indice_llevan_a_secciones_existentes_y_las_imagenes_se_muestran(): void
    {
        $html = Guia::html();

        preg_match_all('/href="#([^"]+)"/u', $html, $enlaces);
        preg_match_all('/id="([^"]+)"/u', $html, $ids);
        $rotos = array_diff(array_unique(array_map('rawurldecode', $enlaces[1])), $ids[1]);

        $this->assertGreaterThan(30, count($enlaces[1]));
        $this->assertSame([], array_values($rotos), 'Enlaces del manual sin sección de destino.');
        $this->assertStringNotContainsString('src="manual/img/', $html, 'Toda imagen del manual debe servirse por la ruta autenticada.');
        $this->assertSame(count(glob(Guia::directorioImagenes().'/*.png')), substr_count($html, '/guia-de-usuario/imagenes/'));
        $this->assertStringContainsString('<table>', $html);
    }

    public function test_la_ruta_de_imagenes_solo_sirve_archivos_del_manual(): void
    {
        $this->actuarComo('Jefatura');

        $this->get($this->urlImagen('01-ingreso.png'))->assertOk()->assertHeader('Content-Type', 'image/png');
        $this->get($this->urlImagen('no-existe.png'))->assertNotFound();
        $this->get('/admin/guia-de-usuario/imagenes/..%2F..%2F.env')->assertNotFound();
        $this->get('/admin/guia-de-usuario/imagenes/MANUAL-USUARIO.md')->assertNotFound();
    }

    /**
     * El manual nombra cada opción del menú tal como aparece en el sistema, para que no vuelva a
     * quedar una sección sin documentar.
     */
    public function test_el_manual_nombra_cada_opcion_del_menu(): void
    {
        $this->actuarComo('super_admin');
        $manual = file_get_contents(Guia::rutaArchivo());

        $etiquetas = collect(Filament::getPanel('admin')->getNavigation())
            ->flatMap(fn ($grupo) => $grupo->getItems())
            ->map(fn (NavigationItem $item): string => $item->getLabel())
            ->unique();

        $this->assertNotEmpty($etiquetas);

        foreach ($etiquetas as $etiqueta) {
            $patron = '/\*\*[^*\n]*(?<!\p{L})'.preg_quote($etiqueta, '/').'(?!\p{L})[^*\n]*\*\*/u';

            $this->assertMatchesRegularExpression($patron, $manual, "El manual no menciona en negrita la opción del menú «{$etiqueta}».");
        }
    }
}
