<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * RF-68 del SRS (Módulo 15): la norma pide informar las reprogramaciones del período y sus
     * causas, incluidas las que después se realizaron. Como `estado` y `observaciones` se
     * sobrescriben al marcar "Realizado", la causa se conserva aparte al reprogramar.
     */
    public function up(): void
    {
        Schema::table('ejecuciones_mensuales', function (Blueprint $table) {
            $table->text('causa_reprogramacion')->nullable()->after('observaciones');
        });
    }

    public function down(): void
    {
        Schema::table('ejecuciones_mensuales', function (Blueprint $table) {
            $table->dropColumn('causa_reprogramacion');
        });
    }
};
