<?php

namespace Tests\Unit\Enums;

use App\Enums\Criticidad;
use App\Enums\FrecuenciaAnual;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * RN-01 y RN-08: meses que programa cada frecuencia y mínimo de mantenciones según criticidad.
 */
class FrecuenciaYCriticidadTest extends TestCase
{
    /**
     * @return array<string, array{FrecuenciaAnual, array<int, int>}>
     */
    public static function frecuencias(): array
    {
        return [
            '1 vez' => [FrecuenciaAnual::Una, [12]],
            '2 veces' => [FrecuenciaAnual::Dos, [6, 12]],
            '3 veces' => [FrecuenciaAnual::Tres, [4, 8, 12]],
            '4 veces' => [FrecuenciaAnual::Cuatro, [3, 6, 9, 12]],
            '6 veces' => [FrecuenciaAnual::Seis, [2, 4, 6, 8, 10, 12]],
            '12 veces' => [FrecuenciaAnual::Doce, range(1, 12)],
        ];
    }

    /**
     * @param  array<int, int>  $meses
     */
    #[DataProvider('frecuencias')]
    public function test_cada_frecuencia_programa_meses_uniformes_que_terminan_en_diciembre(FrecuenciaAnual $frecuencia, array $meses): void
    {
        $this->assertSame($meses, $frecuencia->mesesProgramados());
        $this->assertCount((int) $frecuencia->value, $frecuencia->mesesProgramados());
    }

    public function test_un_equipo_critico_exige_al_menos_dos_mantenciones_al_anio(): void
    {
        $this->assertSame(2, Criticidad::Critico->frecuenciaMinima());
        $this->assertFalse(Criticidad::Critico->admiteFrecuencia(FrecuenciaAnual::Una));
        $this->assertTrue(Criticidad::Critico->admiteFrecuencia(FrecuenciaAnual::Dos));
    }

    public function test_las_demas_criticidades_admiten_una_mantencion_al_anio(): void
    {
        foreach ([Criticidad::Relevante, Criticidad::ImMayorIgual12, Criticidad::NoAplica] as $criticidad) {
            $this->assertSame(1, $criticidad->frecuenciaMinima());
            $this->assertTrue($criticidad->admiteFrecuencia(FrecuenciaAnual::Una));
        }
    }
}
