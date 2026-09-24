<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * RF-35 (BRIEF §5, SAD §6): la autorización por rol se implementa con
     * `spatie/laravel-permission` + `filament-shield`, no con un motor a medida (RNF-06/RNF-08 del
     * SRS). La tabla `roles` propia (creada en `2026_09_07_000100_create_roles_table.php`) queda
     * reemplazada por la que publica `spatie/laravel-permission` en la migración siguiente
     * (`2026_09_08_123944_create_permission_tables.php`) — de ahí el timestamp anterior, para que
     * esta corra primero. No había datos reales de roles cargados (solo un usuario de prueba sin
     * rol asignado), así que no se requiere migrar filas.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('rol_id');
        });

        Schema::dropIfExists('roles');
    }

    public function down(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150)->unique();
            $table->json('permisos')->nullable();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('rol_id')->nullable()->after('id')
                ->constrained('roles')->restrictOnDelete();
        });
    }
};
