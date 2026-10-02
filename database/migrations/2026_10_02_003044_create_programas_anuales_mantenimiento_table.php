<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * RF-72 del SRS (Res. Ex. 1341/2017 §7.2 y §7.3): definición del programa anual de MP de cada
     * establecimiento (a más tardar en marzo) y su validación por la Dirección.
     */
    public function up(): void
    {
        Schema::create('programas_anuales_mantenimiento', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recinto_id')->constrained('recintos')->cascadeOnDelete();
            $table->smallInteger('anio');
            $table->date('fecha_definicion');
            $table->date('fecha_validacion')->nullable();
            $table->string('validado_por_nombre', 150)->nullable();
            $table->string('validado_por_cargo', 150)->nullable();
            $table->string('documento_validacion', 100)->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->unique(['recinto_id', 'anio']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('programas_anuales_mantenimiento');
    }
};
