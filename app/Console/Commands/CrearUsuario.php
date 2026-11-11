<?php

namespace App\Console\Commands;

use App\Models\Recinto;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * SPEC-ME-011: crea un usuario del panel con su rol y, opcionalmente, sus recintos (RF-63). Sirve
 * para el primer administrador de producción, donde el seeder ya no crea usuarios de desarrollo.
 * El resto de los usuarios se puede crear después desde el panel (Administración → Usuarios).
 *
 * Los datos que falten se piden por consola. La contraseña se pide sin mostrarla; sin consola
 * interactiva (`--no-interaction`) y sin `--password`, se genera una segura y se muestra una sola
 * vez. Pasar `--password` en la línea de comandos la deja en el historial de la shell, así que es
 * preferible ingresarla cuando se pide.
 */
#[Signature('app:crear-usuario
    {--nombre= : Nombre completo}
    {--email= : Correo electrónico (con él se inicia sesión)}
    {--rol= : Rol: super_admin, Encargado de Mantención, Técnico Interno, Encargado de Convenios o Jefatura}
    {--recinto=* : Recinto asignado, por nombre o id (repetible). Sin recintos, el usuario ve todos}
    {--password= : Contraseña (si se omite, se pide o se genera)}')]
#[Description('Crea un usuario del panel con su rol y sus recintos asignados.')]
class CrearUsuario extends Command
{
    private const LARGO_CONTRASENA_GENERADA = 20;

    /**
     * SPEC-ME-019: el largo mínimo es el de la política común (`config/seguridad.php`).
     */
    private static function largoMinimo(): int
    {
        return (int) config('seguridad.contrasena.largo_minimo');
    }

    public function handle(): int
    {
        $roles = Role::query()->orderBy('name')->pluck('name');

        if ($roles->isEmpty()) {
            $this->error('No hay roles. Corre primero `php artisan db:seed --force`, que crea los roles y sus permisos.');

            return self::FAILURE;
        }

        $nombre = $this->dato('nombre', fn (): ?string => $this->ask('Nombre completo'));
        $email = $this->dato('email', fn (): ?string => $this->ask('Correo electrónico'));
        $rol = $this->dato('rol', fn (): string => $this->choice('Rol', $roles->all()));

        $validacion = Validator::make(
            ['nombre' => $nombre, 'email' => $email, 'rol' => $rol],
            [
                'nombre' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', 'unique:users,email'],
                'rol' => ['required', 'in:'.$roles->implode(',')],
            ],
            ['rol.in' => 'El rol no existe. Roles disponibles: '.$roles->implode(', ').'.'],
        );

        if ($validacion->fails()) {
            foreach ($validacion->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $recintos = $this->resolverRecintos((array) $this->option('recinto'));

        if ($recintos === null) {
            return self::FAILURE;
        }

        [$contrasena, $generada] = $this->contrasena();

        if ($contrasena === null) {
            return self::FAILURE;
        }

        $usuario = DB::transaction(function () use ($nombre, $email, $rol, $recintos, $contrasena): User {
            $usuario = User::query()->create([
                'name' => $nombre,
                'email' => $email,
                'password' => $contrasena,
                'activo' => true,
            ]);

            $usuario->assignRole($rol);
            $usuario->recintos()->sync($recintos->pluck('id')->all());

            return $usuario;
        });

        $this->info("Usuario creado: {$usuario->email}");
        $this->table(['Nombre', 'Correo', 'Rol', 'Recintos'], [[
            $usuario->name,
            $usuario->email,
            $rol,
            $recintos->isEmpty() ? 'Todos' : $recintos->pluck('nombre')->implode(', '),
        ]]);

        if ($generada) {
            $this->warn('Contraseña generada (se muestra una sola vez; entrégala por un canal seguro y pide cambiarla):');
            $this->line($contrasena);
        }

        return self::SUCCESS;
    }

    /**
     * Valor de una opción o, si falta y la consola es interactiva, el que se pregunta.
     *
     * @param  callable(): ?string  $preguntar
     */
    private function dato(string $opcion, callable $preguntar): ?string
    {
        $valor = $this->option($opcion);

        if (filled($valor)) {
            return trim((string) $valor);
        }

        return $this->input->isInteractive() ? $preguntar() : null;
    }

    /**
     * @param  array<int, string>  $valores
     * @return Collection<int, Recinto>|null null si alguno no existe
     */
    private function resolverRecintos(array $valores): ?Collection
    {
        $recintos = collect();

        foreach (array_filter($valores, 'filled') as $valor) {
            $recinto = ctype_digit((string) $valor)
                ? Recinto::query()->find((int) $valor)
                : Recinto::query()->where('nombre', $valor)->first();

            if ($recinto === null) {
                $disponibles = Recinto::query()->orderBy('nombre')->pluck('nombre')->implode(', ');
                $this->error("El recinto \"{$valor}\" no existe. Recintos disponibles: ".($disponibles ?: 'ninguno').'.');

                return null;
            }

            $recintos->push($recinto);
        }

        return $recintos->unique('id')->values();
    }

    /**
     * @return array{0: string|null, 1: bool} contraseña y si fue generada
     */
    private function contrasena(): array
    {
        $contrasena = $this->option('password');

        if (blank($contrasena) && ! $this->input->isInteractive()) {
            return [Str::password(self::LARGO_CONTRASENA_GENERADA), true];
        }

        if (blank($contrasena)) {
            $contrasena = (string) $this->secret('Contraseña (mínimo '.self::largoMinimo().' caracteres)');

            if ($contrasena !== (string) $this->secret('Repite la contraseña')) {
                $this->error('Las contraseñas no coinciden.');

                return [null, false];
            }
        }

        if (mb_strlen($contrasena) < self::largoMinimo()) {
            $this->error('La contraseña debe tener al menos '.self::largoMinimo().' caracteres.');

            return [null, false];
        }

        return [$contrasena, false];
    }
}
