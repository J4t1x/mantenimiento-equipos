<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SPEC-ME-019 (RF-89, AUDIT §3.1): registro de eventos de autenticación. Solo inserción: el
 * modelo impide modificar o eliminar filas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eventos_autenticacion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('email')->nullable();
            $table->string('evento', 40);
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->json('detalle')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['evento', 'created_at']);
            $table->index('created_at');
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eventos_autenticacion');
    }
};
