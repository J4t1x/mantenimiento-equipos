<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Role;

/**
 * RF-34/RF-35 del SRS: los 4 roles de negocio de `BRIEF.md` §2.3 (el acceso de proveedores
 * externos queda fuera, sujeto a la pregunta abierta 7) más `super_admin` (Shield, sin scope de
 * BRIEF, necesario para administrar el sistema).
 *
 * Los permisos de Shield viven solo en la base de datos (no en una migración): un
 * `migrate:fresh` los borra. Por eso este seeder empieza regenerándolos
 * (`shield:generate --all`), para que `migrate:fresh --seed` deje el entorno completo de un solo
 * comando, sin pasos manuales adicionales. Con `--option=permissions` solo crea permisos: las
 * Policies ya están versionadas en `app/Policies` y no se reescriben en cada corrida.
 */
class RolesAndPermissionsSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Recursos de solo catálogo/consulta, compartidos por varios roles operativos.
     */
    private const CATALOGOS = ['clase_equipo', 'recinto', 'servicio_clinico', 'proveedor'];

    public function run(): void
    {
        Artisan::call('shield:generate', ['--all' => true, '--option' => 'permissions', '--panel' => 'admin', '--no-interaction' => true]);

        $this->rol('Encargado de Mantención', [
            ...$this->crud(['equipo', 'plan_mantenimiento', 'ejecucion_mensual', 'mantenimiento_correctivo', ...self::CATALOGOS]),
            ...$this->lectura(['convenio', 'convenio_ejecucion_mensual']),
            'view.reportes', 'view.cumplimiento_mp_widget', 'view.indicadores_generales_widget',
            'view.alertas_convenios_widget', 'view.catastro_alertas_widget',
            'view.gasto_mensual_widget', 'view.distribucion_catastro_widget',
        ]);

        $this->rol('Técnico Interno', [
            ...$this->lectura(['equipo', 'plan_mantenimiento', ...self::CATALOGOS]),
            'view_any.ejecucion_mensual', 'view.ejecucion_mensual', 'create.ejecucion_mensual', 'update.ejecucion_mensual',
            'view_any.mantenimiento_correctivo', 'view.mantenimiento_correctivo', 'create.mantenimiento_correctivo', 'update.mantenimiento_correctivo',
            'view.cumplimiento_mp_widget', 'view.mis_planes_del_mes_widget',
        ]);

        $this->rol('Encargado de Convenios', [
            ...$this->crud(['convenio', 'convenio_ejecucion_mensual', 'proveedor']),
            ...$this->lectura(['equipo', 'plan_mantenimiento']),
            'view.reportes', 'view.alertas_convenios_widget', 'view.convenios_encargado_widget',
            'view.gasto_mensual_widget',
        ]);

        $this->rol('Jefatura', [
            ...$this->lectura(['equipo', 'plan_mantenimiento', 'ejecucion_mensual', 'mantenimiento_correctivo', 'convenio', 'convenio_ejecucion_mensual', ...self::CATALOGOS]),
            'view.reportes', 'view.cumplimiento_mp_widget', 'view.indicadores_generales_widget',
            'view.catastro_alertas_widget', 'view.gasto_mensual_widget', 'view.distribucion_catastro_widget',
        ]);

        // No hace falta crear ni asignarle permisos a `super_admin` a mano: con
        // `super_admin.define_via_gate` en `false` (config/filament-shield.php), el propio
        // `shield:generate` de la línea de arriba crea ese rol y le entrega cada permiso que
        // genera.
    }

    /**
     * @param  array<int, string>  $permisos
     */
    private function rol(string $nombre, array $permisos): void
    {
        Role::firstOrCreate(['name' => $nombre, 'guard_name' => 'web'])
            ->syncPermissions(array_unique($permisos));
    }

    /**
     * RF-50 (Módulo 12): además de las 5 acciones básicas, se agregan las de soft-delete
     * (`delete_any`, `restore`, `restore_any`, `force_delete`, `force_delete_any`) — sin ellas,
     * un rol con "CRUD completo" no podía usar los botones de restaurar/eliminar definitivo que
     * `EquiposTable`/`ConveniosTable`/`MantenimientoCorrectivosTable` ya mostraban (la Policy
     * generada por Shield exige el permiso correspondiente, nunca otorgado). Otorgarlas también a
     * recursos sin soft-delete (catálogos, ejecución mensual) es inofensivo: no hay ninguna acción
     * en su UI que las use.
     *
     * @param  array<int, string>  $recursos
     * @return array<int, string>
     */
    private function crud(array $recursos): array
    {
        $acciones = [
            'view_any', 'view', 'create', 'update', 'delete',
            'delete_any', 'restore', 'restore_any', 'force_delete', 'force_delete_any',
        ];

        return $this->combinar($acciones, $recursos);
    }

    /**
     * @param  array<int, string>  $recursos
     * @return array<int, string>
     */
    private function lectura(array $recursos): array
    {
        return $this->combinar(['view_any', 'view'], $recursos);
    }

    /**
     * @param  array<int, string>  $acciones
     * @param  array<int, string>  $recursos
     * @return array<int, string>
     */
    private function combinar(array $acciones, array $recursos): array
    {
        $permisos = [];

        foreach ($recursos as $recurso) {
            foreach ($acciones as $accion) {
                $permisos[] = "{$accion}.{$recurso}";
            }
        }

        return $permisos;
    }
}
