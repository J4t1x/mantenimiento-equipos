<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Roles del sistema (BRIEF §2 / SRS §2.3): Encargado de Mantención, Responsable técnico de
     * recinto, Técnico interno/Proveedor externo, Encargado de convenios, Jefatura de servicio
     * clínico. Sin motor de permisos a medida (MODELO-DATOS §3.11): `permisos` queda como
     * referencia informativa, la autorización real no está en el alcance actual.
     */
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150)->unique();
            $table->json('permisos')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
