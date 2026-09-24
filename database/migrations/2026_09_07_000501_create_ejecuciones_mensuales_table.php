<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bitácora de ejecución mensual (MODELO-DATOS §3.7): equivalente digital de los símbolos
     * X/√/ꓣ de la planilla. La validación de transición de estado (RN-02) es funcionalidad aún
     * no implementada en esta etapa.
     */
    public function up(): void
    {
        Schema::create('ejecuciones_mensuales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_mantenimiento_id')->constrained('planes_mantenimiento')->restrictOnDelete();
            $table->unsignedTinyInteger('mes');
            $table->enum('estado', ['sin_programar', 'programado', 'realizado', 'reprogramado'])
                ->default('sin_programar');
            $table->date('fecha_real')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->unique(['plan_mantenimiento_id', 'mes']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ejecuciones_mensuales');
    }
};
