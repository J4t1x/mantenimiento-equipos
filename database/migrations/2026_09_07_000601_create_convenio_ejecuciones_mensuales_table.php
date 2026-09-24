<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecución mensual de convenios (MODELO-DATOS §3.10). La alerta de sobregiro (RN-05) se
     * calcula en tiempo de lectura sobre esta tabla (`CalcularEjecucionConvenioAction`); el índice
     * no es único porque un convenio puede tener más de una orden de compra/factura en el mismo
     * mes.
     */
    public function up(): void
    {
        Schema::create('convenio_ejecuciones_mensuales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('convenio_id')->constrained('convenios')->restrictOnDelete();
            $table->smallInteger('anio');
            $table->unsignedTinyInteger('mes');
            $table->string('n_orden_compra', 50);
            $table->decimal('monto', 12, 2);
            $table->timestamps();

            $table->index(['convenio_id', 'anio', 'mes']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('convenio_ejecuciones_mensuales');
    }
};
