<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * RF-73 del SRS (Res. Ex. 1341/2017 §7.4.iii): retiro de uso de un equipo con evidencia escrita,
     * y su eventual reingreso. Un retiro sin `fecha_reingreso` está abierto: el equipo sigue retirado.
     */
    public function up(): void
    {
        Schema::create('retiros_uso', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipo_id')->constrained('equipos')->restrictOnDelete();
            $table->date('fecha_retiro');
            $table->text('motivo');
            $table->string('documento_retiro', 100);
            $table->date('fecha_reingreso')->nullable();
            $table->string('documento_reingreso', 100)->nullable();
            $table->foreignId('registrado_por_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['equipo_id', 'fecha_reingreso']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retiros_uso');
    }
};
