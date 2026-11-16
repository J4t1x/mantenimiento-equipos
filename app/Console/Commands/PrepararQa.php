<?php

namespace App\Console\Commands;

use App\Enums\Criticidad;
use App\Enums\EstadoEjecucion;
use App\Enums\EstadoEquipo;
use App\Enums\Propiedad;
use App\Models\Equipo;
use App\Models\PlanMantenimiento;
use App\Models\Proveedor;
use App\Models\Recinto;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use RuntimeException;
use Spatie\Permission\Models\Role;

/**
 * SPEC-ME-021: deja el ambiente de pruebas (QA) listo para la capacitación y la prueba de aceptación
 * (docs/PAUTA-PRUEBA-ACEPTACION.md, sección 3), después de importar el catastro del recinto principal.
 *
 * - Una cuenta por perfil, más una cuenta de seguridad para los casos de bloqueo y recuperación de
 *   contraseña (S-03 a S-05), para no bloquear la cuenta de un participante durante la sesión.
 * - El "Recinto de prueba" con un equipo (Equipo D) y un proveedor de prueba.
 * - El Técnico Interno como responsable interno de dos planes (Equipos A y B), y un tercer plan sin
 *   asignar (Equipo C).
 * - Al final, la tabla de cuentas y de equipos de referencia que se copia en la pauta.
 *
 * Todo ocurre en una transacción: si falta un equipo de referencia, no queda nada creado. Se puede
 * ejecutar más de una vez: reutiliza lo que ya creó y solo muestra contraseñas nuevas. No se
 * ejecuta en producción, donde las cuentas de prueba no deben existir.
 */
#[Signature('app:preparar-qa
    {--recinto=Hospital Regional Coyhaique : Recinto cuyo catastro se usa en la sesión}
    {--anio= : Año de los planes (por defecto, el año en curso)}
    {--dominio=prueba.invalid : Dominio de los correos de las cuentas de prueba}
    {--correo-seguridad= : Correo con bandeja accesible para los casos S-03 a S-05}
    {--nuevas-contrasenas : Genera contraseñas nuevas para las cuentas de prueba que ya existen}')]
#[Description('Prepara el ambiente de pruebas (QA) para la capacitación y la prueba de aceptación.')]
class PrepararQa extends Command
{
    public const RECINTO_PRUEBA = 'Recinto de prueba';

    public const PROVEEDOR_PRUEBA = 'Proveedor de prueba (QA)';

    public const INVENTARIO_EQUIPO_D = 'QA-0001';

    private const LARGO_CONTRASENA = 16;

    /**
     * Cuentas de prueba: clave del correo, perfil, nombre y si se restringe al recinto principal.
     *
     * @var array<int, array{clave: string, rol: string, nombre: string, recinto: bool, perfil: string}>
     */
    private const CUENTAS = [
        ['clave' => 'qa.admin', 'rol' => 'super_admin', 'nombre' => 'QA Administrador', 'recinto' => false, 'perfil' => 'Administrador (super_admin)'],
        ['clave' => 'qa.mantencion', 'rol' => 'Encargado de Mantención', 'nombre' => 'QA Encargado de Mantención', 'recinto' => true, 'perfil' => 'Encargado de Mantención'],
        ['clave' => 'qa.tecnico', 'rol' => 'Técnico Interno', 'nombre' => 'QA Técnico Interno', 'recinto' => true, 'perfil' => 'Técnico Interno'],
        ['clave' => 'qa.convenios', 'rol' => 'Encargado de Convenios', 'nombre' => 'QA Encargado de Convenios', 'recinto' => false, 'perfil' => 'Encargado de Convenios'],
        ['clave' => 'qa.jefatura', 'rol' => 'Jefatura', 'nombre' => 'QA Jefatura', 'recinto' => false, 'perfil' => 'Jefatura'],
    ];

    public function handle(): int
    {
        if (app()->environment('production')) {
            $this->error('Este comando crea cuentas y datos de prueba: no se ejecuta con APP_ENV=production. En QA usa APP_ENV=staging (docs/INSTALACION-QA.md).');

            return self::FAILURE;
        }

        if (Role::query()->count() === 0) {
            $this->error('No hay roles. Corre primero `php artisan db:seed --force`.');

            return self::FAILURE;
        }

        $anio = (int) ($this->option('anio') ?: now()->year);
        $dominio = Str::lower(trim((string) $this->option('dominio')));
        $correoSeguridad = filled($this->option('correo-seguridad'))
            ? Str::lower(trim((string) $this->option('correo-seguridad')))
            : "qa.seguridad@{$dominio}";

        if (Validator::make(['correo' => $correoSeguridad, 'otro' => "qa.admin@{$dominio}"], ['correo' => 'email', 'otro' => 'email'])->fails()) {
            $this->error('El dominio o el correo de seguridad no son válidos.');

            return self::FAILURE;
        }

        $recinto = Recinto::query()->where('nombre', $this->option('recinto'))->first();

        if ($recinto === null || ! $recinto->equipos()->exists()) {
            $this->error("El recinto \"{$this->option('recinto')}\" no tiene equipos. Importa primero su catastro con `php artisan app:importar-catastro-coyhaique`.");

            return self::FAILURE;
        }

        try {
            return DB::transaction(fn (): int => $this->preparar($recinto, $anio, $dominio, $correoSeguridad));
        } catch (RuntimeException $faltaEquipo) {
            $this->error($faltaEquipo->getMessage());

            return self::FAILURE;
        }
    }

    private function preparar(Recinto $recinto, int $anio, string $dominio, string $correoSeguridad): int
    {
        $contrasenas = [];
        $usuarios = [];

        foreach (self::CUENTAS as $cuenta) {
            $usuarios[$cuenta['clave']] = $this->cuenta(
                "{$cuenta['clave']}@{$dominio}", $cuenta['nombre'], $cuenta['rol'], $cuenta['recinto'] ? $recinto : null, $contrasenas,
            );
        }

        $usuarios['qa.seguridad'] = $this->cuenta($correoSeguridad, 'QA Cuenta de seguridad', 'Jefatura', null, $contrasenas);

        $tecnico = $usuarios['qa.tecnico'];
        $planA = $this->planEquipoA($recinto, $anio, $tecnico);

        if ($planA === null) {
            throw new RuntimeException("No hay un equipo crítico activo sin garantía vigente en {$anio} con meses programados en \"{$recinto->nombre}\" para el Equipo A.");
        }

        $planB = $this->planEquipoB($recinto, $anio, $tecnico, $planA);
        $planC = $planB === null ? null : $this->planEquipoC($recinto, $anio, [$planA, $planB]);

        if ($planB === null || $planC === null) {
            throw new RuntimeException("No hay planes {$anio} suficientes en \"{$recinto->nombre}\" para los Equipos B y C.");
        }

        $planA->update(['responsable_interno_id' => $tecnico->id]);
        $planB->update(['responsable_interno_id' => $tecnico->id]);

        $equipoA = $planA->equipo;
        $equipoD = $this->equipoD($equipoA);

        Proveedor::query()->firstOrCreate(['nombre' => self::PROVEEDOR_PRUEBA], ['activo' => true]);

        $this->info("Ambiente de pruebas preparado con el catastro de \"{$recinto->nombre}\", planes {$anio}.");
        $this->newLine();

        $this->line('<options=bold>Cuentas de prueba</>');
        $this->table(['Perfil', 'Correo', 'Recinto', 'Contraseña'], collect(self::CUENTAS)
            ->map(fn (array $cuenta): array => [
                $cuenta['perfil'],
                $usuarios[$cuenta['clave']]->email,
                $cuenta['recinto'] ? $recinto->nombre : 'Todos',
                $contrasenas[$usuarios[$cuenta['clave']]->email] ?? '(sin cambios)',
            ])
            ->push(['Cuenta de seguridad (S-03 a S-05, Jefatura)', $correoSeguridad, 'Todos', $contrasenas[$correoSeguridad] ?? '(sin cambios)'])
            ->all());

        if ($contrasenas !== []) {
            $this->warn('Las contraseñas se muestran una sola vez. Entrégalas a cada participante por un canal distinto de la videollamada.');
        }

        if (blank($this->option('correo-seguridad'))) {
            $this->warn('Sin --correo-seguridad, la cuenta de seguridad no recibe correos: los casos S-04 y S-05 quedan No aplica.');
        }

        $this->newLine();
        $this->line('<options=bold>Equipos de referencia (pauta, sección 3)</>');
        $this->table(['Referencia', 'N° de inventario', 'Equipo', 'Detalle'], [
            ['Equipo A', $equipoA->n_inventario, $equipoA->nombre, 'Crítico sin garantía vigente, plan asignado al técnico'],
            ['Equipo B', $planB->equipo->n_inventario, $planB->equipo->nombre, 'Plan asignado al técnico'],
            ['Equipo C', $planC->equipo->n_inventario, $planC->equipo->nombre, 'Plan no asignado al técnico'],
            ['Equipo D', $equipoD->n_inventario, $equipoD->nombre, self::RECINTO_PRUEBA],
            ['Servicio clínico en uso', '', $equipoA->servicioClinico->nombre, 'Servicio del Equipo A (caso EM-11)'],
        ]);

        return self::SUCCESS;
    }

    /**
     * Crea la cuenta o reutiliza la existente. Las contraseñas generadas quedan en `$contrasenas`.
     *
     * @param  array<string, string>  $contrasenas
     */
    private function cuenta(string $email, string $nombre, string $rol, ?Recinto $recinto, array &$contrasenas): User
    {
        $usuario = User::query()->where('email', $email)->first();

        if ($usuario === null || $this->option('nuevas-contrasenas')) {
            $contrasenas[$email] = Str::password(self::LARGO_CONTRASENA, symbols: false);
        }

        $usuario ??= new User(['email' => $email]);
        $usuario->fill(['name' => $nombre, 'activo' => true]);

        if (isset($contrasenas[$email])) {
            $usuario->password = $contrasenas[$email];
        }

        $usuario->save();
        $usuario->syncRoles([$rol]);
        $usuario->recintos()->sync($recinto === null ? [] : [$recinto->id]);

        return $usuario;
    }

    /**
     * Planes del año en equipos activos del recinto, con meses programados coherentes con la
     * frecuencia (sin la alerta de meses inconsistentes, para no confundir en la sesión).
     *
     * @return Builder<PlanMantenimiento>
     */
    private function planes(Recinto $recinto, int $anio): Builder
    {
        return PlanMantenimiento::query()
            ->where('anio', $anio)
            ->whereHas('equipo', fn (Builder $equipo) => $equipo->where('recinto_id', $recinto->id)->where('activo', true))
            ->whereNotIn('id', PlanMantenimiento::query()->conMesesInconsistentes()->select('id'))
            ->whereHas('ejecucionesMensuales', fn (Builder $mes) => $mes->where('estado', EstadoEjecucion::Programado))
            ->with('equipo.servicioClinico')
            ->orderBy('id');
    }

    /**
     * Equipo A: crítico sin garantía vigente en el año (EM-07 rechaza la frecuencia 1), con un mes
     * programado (TI-03), asignado al técnico. Si ya se asignó en una ejecución anterior, se reutiliza.
     */
    private function planEquipoA(Recinto $recinto, int $anio, User $tecnico): ?PlanMantenimiento
    {
        $esCriticoSinGarantia = fn (Builder $equipo) => $equipo
            ->where('criticidad', Criticidad::Critico)
            ->where(fn (Builder $garantia) => $garantia
                ->where('en_garantia', false)
                ->orWhere('garantia_anio_vencimiento', '<', $anio));

        return $this->planes($recinto, $anio)->where('responsable_interno_id', $tecnico->id)->whereHas('equipo', $esCriticoSinGarantia)->first()
            ?? $this->planes($recinto, $anio)->whereNull('responsable_interno_id')->whereHas('equipo', $esCriticoSinGarantia)->first();
    }

    /**
     * Equipo B: otro plan asignado al técnico, con al menos dos meses programados y uno ya pasado
     * (TI-02 lo registra como realizado; EM-08 reprograma otro). De otro tipo que A.
     */
    private function planEquipoB(Recinto $recinto, int $anio, User $tecnico, PlanMantenimiento $planA): ?PlanMantenimiento
    {
        $mesActual = $anio < now()->year ? 13 : ($anio > now()->year ? 1 : now()->month);

        $candidatos = fn (): Builder => $this->planes($recinto, $anio)
            ->whereKeyNot($planA->id)
            ->whereHas('equipo', fn (Builder $equipo) => $equipo->where('nombre', '<>', $planA->equipo->nombre))
            ->whereHas('ejecucionesMensuales', fn (Builder $mes) => $mes->where('estado', EstadoEjecucion::Programado), '>=', 2)
            ->whereHas('ejecucionesMensuales', fn (Builder $mes) => $mes->where('estado', EstadoEjecucion::Programado)->where('mes', '<', $mesActual));

        return $candidatos()->where('responsable_interno_id', $tecnico->id)->first()
            ?? $candidatos()->whereNull('responsable_interno_id')->first();
    }

    /**
     * Equipo C: plan sin responsable interno (TI-04: el técnico lo ve, pero no registra; A-02 se lo
     * asigna a otro usuario). De otro tipo que A y B, para no confundirlos durante la sesión.
     *
     * @param  array<int, PlanMantenimiento>  $asignados
     */
    private function planEquipoC(Recinto $recinto, int $anio, array $asignados): ?PlanMantenimiento
    {
        $nombres = collect($asignados)->map(fn (PlanMantenimiento $plan): string => $plan->equipo->nombre)->unique()->all();

        return $this->planes($recinto, $anio)
            ->whereNull('responsable_interno_id')
            ->whereKeyNot(collect($asignados)->pluck('id')->all())
            ->whereHas('equipo', fn (Builder $equipo) => $equipo->whereNotIn('nombre', $nombres))
            ->first();
    }

    /**
     * Equipo D: equipo del Recinto de prueba, que el Encargado de Mantención no debe ver (EM-12).
     */
    private function equipoD(Equipo $referencia): Equipo
    {
        $recintoPrueba = Recinto::query()->firstOrCreate(['nombre' => self::RECINTO_PRUEBA], ['activo' => true]);

        return Equipo::query()->firstOrCreate(
            ['recinto_id' => $recintoPrueba->id, 'n_inventario' => self::INVENTARIO_EQUIPO_D],
            [
                'servicio_clinico_id' => $referencia->servicio_clinico_id,
                'clase_id' => $referencia->clase_id,
                'nombre' => 'Equipo de prueba (QA)',
                'propiedad' => Propiedad::Propio,
                'estado' => EstadoEquipo::Bueno,
                'criticidad' => Criticidad::Relevante,
                'activo' => true,
            ],
        );
    }
}
