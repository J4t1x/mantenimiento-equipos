<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * RF-75 del SRS (Res. Ex. 1341/2017, "Definiciones"): tipo de equipo crítico de la norma.
     * Nulo = sin clasificar.
     */
    public function up(): void
    {
        Schema::table('equipos', function (Blueprint $table) {
            $table->string('tipo_critico_norma', 50)->nullable()->after('criticidad');
            $table->index('tipo_critico_norma');
        });
    }

    public function down(): void
    {
        Schema::table('equipos', function (Blueprint $table) {
            $table->dropIndex(['tipo_critico_norma']);
            $table->dropColumn('tipo_critico_norma');
        });
    }
};
