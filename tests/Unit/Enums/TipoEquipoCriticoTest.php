<?php

namespace Tests\Unit\Enums;

use App\Enums\TipoEquipoCritico;
use PHPUnit\Framework\TestCase;

/**
 * RF-75: sugerencia del tipo de equipo crítico de la norma a partir del nombre del equipo.
 */
class TipoEquipoCriticoTest extends TestCase
{
    public function test_sugiere_el_tipo_segun_el_nombre_sin_importar_mayusculas_ni_tildes(): void
    {
        $this->assertSame(TipoEquipoCritico::MonitorDesfibrilador, TipoEquipoCritico::sugerirPorNombre('Monitor Desfibrilador'));
        $this->assertSame(TipoEquipoCritico::VentiladorMecanico, TipoEquipoCritico::sugerirPorNombre('VENTILADOR Pediátrico Invasivo'));
        $this->assertSame(TipoEquipoCritico::MaquinaDialisis, TipoEquipoCritico::sugerirPorNombre('Máquina de diálisis'));
        $this->assertSame(TipoEquipoCritico::MaquinaAnestesia, TipoEquipoCritico::sugerirPorNombre('Máquina de anestesia'));
    }

    public function test_no_sugiere_cuando_el_nombre_no_basta(): void
    {
        $this->assertNull(TipoEquipoCritico::sugerirPorNombre('Planta de diálisis'));
        $this->assertNull(TipoEquipoCritico::sugerirPorNombre('Monitor multiparámetro'), 'La monitorización invasiva se clasifica a mano.');
    }

    public function test_solo_no_corresponde_permite_otra_criticidad(): void
    {
        $this->assertFalse(TipoEquipoCritico::NoCorresponde->exigeCriticidadCritica());
        $this->assertTrue(TipoEquipoCritico::Incubadora->exigeCriticidadCritica());
    }
}
