<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * RF-63 del SRS (Módulo 15): recintos asignados a cada usuario. Un usuario sin filas aquí
     * (personal del subdepartamento del SSA) ve todos los recintos; uno con filas, solo esos.
     */
    public function up(): void
    {
        Schema::create('recinto_user', function (Blueprint $table) {
            $table->foreignId('recinto_id')->constrained('recintos')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->primary(['recinto_id', 'user_id']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recinto_user');
    }
};
