<?php

namespace Tests\Feature\Panel;

use App\Enums\EstadoEjecucion;
use App\Enums\FrecuenciaAnual;
use App\Exceptions\EquipoNoAsignadoException;
use App\Exceptions\ExcepcionDeDominio;
use App\Exceptions\FrecuenciaInsuficienteException;
use App\Exceptions\RecintoFueraDeAlcanceException;
use App\Exceptions\ReprogramacionSinCausaException;
use App\Exceptions\RetiroUsoInvalidoException;
use App\Exceptions\TipoCriticoSinCriticidadException;
use App\Exceptions\TransicionBitacoraInvalidaException;
use App\Filament\Support\NotificarExcepcionesDeDominio;
use App\Models\EjecucionMensual;
use App\Models\PlanMantenimiento;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Component;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

/**
 * Corrección de RNF-04 (SPEC-ME-002): una excepción de dominio que llega al panel se muestra como
 * notificación, sin error 500 y sin guardar el cambio. Las demás excepciones se propagan igual.
 */
class ExcepcionesDeDominioTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{class-string}>
     */
    public static function excepciones(): array
    {
        return collect([
            EquipoNoAsignadoException::class,
            FrecuenciaInsuficienteException::class,
            RecintoFueraDeAlcanceException::class,
            ReprogramacionSinCausaException::class,
            RetiroUsoInvalidoException::class,
            TipoCriticoSinCriticidadException::class,
            TransicionBitacoraInvalidaException::class,
        ])->mapWithKeys(fn (string $clase): array => [class_basename($clase) => [$clase]])->all();
    }

    #[DataProvider('excepciones')]
    public function test_las_excepciones_del_modelo_son_de_dominio(string $clase): void
    {
        $this->assertTrue(is_subclass_of($clase, ExcepcionDeDominio::class));
    }

    public function test_una_regla_violada_en_el_modelo_se_notifica_sin_guardar(): void
    {
        $this->actuarComo('Encargado de Mantención');
        $plan = PlanMantenimiento::factory()->create(['anio' => 2026, 'frecuencia_anual' => FrecuenciaAnual::Dos]);
        $mesSinProgramar = $plan->ejecucionesMensuales()->where('estado', EstadoEjecucion::SinProgramar)->first();

        Livewire::test(MarcarRealizadoSinValidarFormulario::class, ['ejecucionId' => $mesSinProgramar->id])
            ->call('guardar')
            ->assertNotified(NotificarExcepcionesDeDominio::TITULO)
            ->assertSet('guardado', false);

        $this->assertSame(EstadoEjecucion::SinProgramar, $mesSinProgramar->fresh()->estado);
    }

    public function test_las_demas_excepciones_se_siguen_propagando(): void
    {
        $this->actuarComo('Encargado de Mantención');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Error inesperado');

        Livewire::test(MarcarRealizadoSinValidarFormulario::class, ['ejecucionId' => 0])
            ->call('fallar');
    }
}

/**
 * Componente mínimo que guarda directo en el modelo, sin la validación del formulario de Filament:
 * reproduce el caso en que solo la guarda del modelo ataja la regla.
 */
class MarcarRealizadoSinValidarFormulario extends Component
{
    public int $ejecucionId;

    public bool $guardado = false;

    public function guardar(): void
    {
        EjecucionMensual::findOrFail($this->ejecucionId)->update(['estado' => EstadoEjecucion::Realizado]);

        $this->guardado = true;
    }

    public function fallar(): void
    {
        throw new RuntimeException('Error inesperado');
    }

    public function render(): string
    {
        return '<div></div>';
    }
}
