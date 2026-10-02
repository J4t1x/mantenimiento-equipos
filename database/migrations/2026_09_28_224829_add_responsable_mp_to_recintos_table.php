<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * RF-70 del SRS (Res. Ex. 1341/2017 §7.1): profesional responsable de la MP de cada
     * establecimiento, designado formalmente por la Subdirección Administrativa.
     */
    public function up(): void
    {
        Schema::table('recintos', function (Blueprint $table) {
            $table->string('responsable_mp_nombre', 150)->nullable()->after('nombre');
            $table->string('responsable_mp_cargo', 150)->nullable()->after('responsable_mp_nombre');
            $table->string('responsable_mp_documento', 100)->nullable()->after('responsable_mp_cargo');
            $table->date('responsable_mp_fecha_designacion')->nullable()->after('responsable_mp_documento');
        });
    }

    public function down(): void
    {
        Schema::table('recintos', function (Blueprint $table) {
            $table->dropColumn([
                'responsable_mp_nombre',
                'responsable_mp_cargo',
                'responsable_mp_documento',
                'responsable_mp_fecha_designacion',
            ]);
        });
    }
};
