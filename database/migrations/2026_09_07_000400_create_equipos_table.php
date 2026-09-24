<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Catastro de equipos médicos (MODELO-DATOS §3.5). `estado` admite `sin_evaluar` (RF-12) para
     * los 59/469 equipos sin dato en la planilla actual. Vida útil residual no se persiste, se
     * calcula en tiempo de lectura (RF-09).
     */
    public function up(): void
    {
        Schema::create('equipos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recinto_id')->constrained('recintos')->restrictOnDelete();
            $table->foreignId('servicio_clinico_id')->constrained('servicios_clinicos')->restrictOnDelete();
            $table->foreignId('clase_id')->constrained('clases_equipo')->restrictOnDelete();
            $table->foreignId('subclase_id')->nullable()->constrained('clases_equipo')->restrictOnDelete();
            $table->string('nombre', 150);
            $table->string('marca', 100)->nullable();
            $table->string('modelo', 100)->nullable();
            $table->string('serie', 100)->nullable();
            $table->string('n_inventario', 50);
            $table->smallInteger('anio_adquisicion')->nullable();
            $table->smallInteger('vida_util_anios')->nullable();
            $table->enum('propiedad', ['propio', 'arriendo', 'comodato', 'prestamo']);
            $table->enum('estado', ['bueno', 'regular', 'malo', 'sin_evaluar'])->default('sin_evaluar');
            $table->enum('criticidad', ['critico', 'relevante', 'im_mayor_igual_12', 'no_aplica']);
            $table->boolean('en_garantia')->default(false);
            $table->smallInteger('garantia_anio_vencimiento')->nullable();
            $table->boolean('bajo_plan_mp')->default(false);
            $table->smallInteger('anio_ingreso_plan')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['recinto_id', 'n_inventario']);
            $table->index('servicio_clinico_id');
            $table->index('criticidad');
            $table->index('estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipos');
    }
};
