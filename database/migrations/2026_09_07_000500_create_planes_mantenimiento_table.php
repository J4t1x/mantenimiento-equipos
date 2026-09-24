<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Plan anual de mantenimiento preventivo por equipo (MODELO-DATOS §3.6). La generación
     * automática de las marcas "Programado" (RF-13/RN-01) es funcionalidad aún no implementada
     * en esta etapa: por ahora el plan solo se registra.
     */
    public function up(): void
    {
        Schema::create('planes_mantenimiento', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipo_id')->constrained('equipos')->restrictOnDelete();
            $table->smallInteger('anio');
            $table->enum('frecuencia_anual', ['1', '2', '3', '4', '6', '12']);
            $table->enum('tipo_mantenimiento', ['interno', 'externo']);
            $table->foreignId('proveedor_id')->nullable()->constrained('proveedores')->restrictOnDelete();
            $table->foreignId('responsable_interno_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('convenio_id')->nullable()->constrained('convenios')->restrictOnDelete();
            $table->decimal('costo_anual_referencia', 12, 2)->nullable();
            $table->timestamps();

            $table->unique(['equipo_id', 'anio']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planes_mantenimiento');
    }
};
