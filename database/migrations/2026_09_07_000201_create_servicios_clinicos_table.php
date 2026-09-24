<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Catálogo de servicios clínicos (MODELO-DATOS §3.2), reemplaza las 110+ variantes de texto
     * libre detectadas en la planilla. Relación 1:N con equipos por defecto (PA-3 del BRIEF sin
     * resolver); si se confirma M:N se agrega una tabla pivote sin romper este catálogo.
     */
    public function up(): void
    {
        Schema::create('servicios_clinicos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150)->unique();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('servicios_clinicos');
    }
};
