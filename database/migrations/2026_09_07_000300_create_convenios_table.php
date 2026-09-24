<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Convenios de mantenimiento con proveedores (MODELO-DATOS §3.9). `subasignacion_sigfe` es
     * referencia manual, sin integración real (PA-5 del BRIEF, ADR-05 del SAD).
     */
    public function up(): void
    {
        Schema::create('convenios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proveedor_id')->constrained('proveedores')->restrictOnDelete();
            $table->string('nombre', 150);
            $table->string('n_resolucion', 50);
            $table->date('fecha_resolucion');
            $table->date('fecha_expiracion');
            $table->decimal('monto_anual', 14, 2);
            $table->string('subasignacion_sigfe', 50)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('convenios');
    }
};
