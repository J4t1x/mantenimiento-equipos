<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * RF-74 del SRS (Res. Ex. 1341/2017 §7.4.ii): n° o referencia del documento formal que
     * justifica una MP no ejecutada. Se registra la referencia, no el archivo.
     */
    public function up(): void
    {
        Schema::table('ejecuciones_mensuales', function (Blueprint $table) {
            $table->string('documento_justificacion', 100)->nullable()->after('causa_reprogramacion');
        });
    }

    public function down(): void
    {
        Schema::table('ejecuciones_mensuales', function (Blueprint $table) {
            $table->dropColumn('documento_justificacion');
        });
    }
};
