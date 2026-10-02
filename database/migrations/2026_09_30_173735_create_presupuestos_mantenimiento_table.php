<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * RF-76 del SRS (Módulo 17): gasto programado anual de mantenimiento preventivo y correctivo de
     * cada recinto, tal como lo trae la cabecera de la planilla ("GASTO PROGRAMADO MP/MC").
     */
    public function up(): void
    {
        Schema::create('presupuestos_mantenimiento', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recinto_id')->constrained('recintos')->cascadeOnDelete();
            $table->smallInteger('anio');
            $table->decimal('gasto_programado_mp', 14, 2)->nullable();
            $table->decimal('gasto_programado_mc', 14, 2)->nullable();
            $table->timestamps();

            $table->unique(['recinto_id', 'anio']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('presupuestos_mantenimiento');
    }
};
