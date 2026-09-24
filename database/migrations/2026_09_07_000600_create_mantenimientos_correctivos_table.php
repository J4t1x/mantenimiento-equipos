<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Eventos de mantenimiento correctivo por equipo (MODELO-DATOS §3.8). `tipo_gasto` queda
     * como texto libre hasta que PA-6 del BRIEF confirme categorías fijas.
     */
    public function up(): void
    {
        Schema::create('mantenimientos_correctivos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipo_id')->constrained('equipos')->restrictOnDelete();
            $table->date('fecha');
            $table->text('falla_descripcion');
            $table->decimal('costo', 12, 2);
            $table->string('tipo_gasto', 100)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mantenimientos_correctivos');
    }
};
