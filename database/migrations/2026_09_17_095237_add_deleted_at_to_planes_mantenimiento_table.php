<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * RF-50 del SRS (Módulo 12): protege el borrado de un plan de mantenimiento con el mismo
     * criterio que Equipo/Convenio/Mantenimiento correctivo (soft-delete), en vez de borrarlo (y
     * sus 12 ejecuciones mensuales asociadas) sin rastro ni forma de recuperarlo.
     */
    public function up(): void
    {
        Schema::table('planes_mantenimiento', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('planes_mantenimiento', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
