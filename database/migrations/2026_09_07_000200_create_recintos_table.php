<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Catálogo de recintos/establecimientos (MODELO-DATOS §3.1). Hoy la planilla trae solo el
     * Hospital Regional Coyhaique; se modela como catálogo desde el inicio (PA-1 del BRIEF).
     */
    public function up(): void
    {
        Schema::create('recintos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150)->unique();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recintos');
    }
};
