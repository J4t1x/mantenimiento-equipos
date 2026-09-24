<?php

namespace Tests\Feature\Panel;

use App\Filament\Pages\Reportes;
use App\Filament\Resources\ClaseEquipos\ClaseEquipoResource;
use App\Filament\Resources\ConvenioEjecucionesMensuales\ConvenioEjecucionMensualResource;
use App\Filament\Resources\Convenios\ConvenioResource;
use App\Filament\Resources\EjecucionesMensuales\EjecucionMensualResource;
use App\Filament\Resources\Equipos\EquipoResource;
use App\Filament\Resources\MantenimientoCorrectivos\MantenimientoCorrectivoResource;
use App\Filament\Resources\PlanMantenimientos\PlanMantenimientoResource;
use App\Filament\Resources\Proveedores\ProveedorResource;
use App\Filament\Resources\Recintos\RecintoResource;
use App\Filament\Resources\ServicioClinicos\ServicioClinicoResource;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * RF-34/RF-35: autenticación obligatoria y acceso a cada pantalla según el rol.
 */
class AccesoPorRolTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_raiz_lleva_al_panel_y_exige_autenticacion(): void
    {
        $this->get('/')->assertRedirect('/admin');
        $this->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_un_usuario_desactivado_no_entra_al_panel(): void
    {
        $this->actingAs(User::factory()->create(['activo' => false]));

        $this->get('/admin')->assertForbidden();
    }

    public function test_el_administrador_abre_todas_las_pantallas(): void
    {
        $this->actuarComo('super_admin');

        $urls = [
            '/admin',
            Reportes::getUrl(),
            ...array_map(fn (string $recurso): string => $recurso::getUrl('index'), [
                ClaseEquipoResource::class, ConvenioEjecucionMensualResource::class, ConvenioResource::class,
                EjecucionMensualResource::class, EquipoResource::class, MantenimientoCorrectivoResource::class,
                PlanMantenimientoResource::class, ProveedorResource::class, RecintoResource::class,
                ServicioClinicoResource::class, UserResource::class,
            ]),
        ];

        foreach ($urls as $url) {
            $this->get($url)->assertOk();
        }
    }

    /**
     * @return array<string, array{string, array<int, class-string>, array<int, class-string>}>
     */
    public static function roles(): array
    {
        return [
            'Encargado de Mantención' => ['Encargado de Mantención', [EquipoResource::class, PlanMantenimientoResource::class, ConvenioResource::class], [UserResource::class]],
            'Técnico Interno' => ['Técnico Interno', [EquipoResource::class, EjecucionMensualResource::class], [ConvenioResource::class, UserResource::class]],
            'Encargado de Convenios' => ['Encargado de Convenios', [ConvenioResource::class, ProveedorResource::class], [RecintoResource::class, UserResource::class]],
            'Jefatura' => ['Jefatura', [EquipoResource::class, ConvenioResource::class], [UserResource::class]],
        ];
    }

    /**
     * @param  array<int, class-string>  $permitidos
     * @param  array<int, class-string>  $denegados
     */
    #[DataProvider('roles')]
    public function test_cada_rol_ve_solo_sus_pantallas(string $rol, array $permitidos, array $denegados): void
    {
        $this->actuarComo($rol);

        $this->get('/admin')->assertOk();

        foreach ($permitidos as $recurso) {
            $this->get($recurso::getUrl('index'))->assertOk();
        }

        foreach ($denegados as $recurso) {
            $this->get($recurso::getUrl('index'))->assertForbidden();
        }
    }

    public function test_reportes_solo_para_roles_con_permiso(): void
    {
        $this->actuarComo('Técnico Interno');

        $this->get(Reportes::getUrl())->assertForbidden();
    }
}
