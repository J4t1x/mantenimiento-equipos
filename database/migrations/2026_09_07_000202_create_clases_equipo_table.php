<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Clase/subclase de equipo (MODELO-DATOS §3.3): tabla auto-referenciada de dos niveles.
     * `clase_padre_id` nulo = es una clase; con valor = es una subclase de esa clase.
     */
    public function up(): void
    {
        Schema::create('clases_equipo', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150);
            $table->foreignId('clase_padre_id')->nullable()
                ->constrained('clases_equipo')->restrictOnDelete();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clases_equipo');
    }
};
