<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * RF-47 (Módulo 12): tabla estándar de `Illuminate\Notifications\Notifiable` (ya usada por
     * `User`), habilita las notificaciones persistentes de Filament (`->databaseNotifications()`)
     * para la alerta de sobregiro de convenio (RN-05) y los rechazos de borrado por integridad
     * referencial — hoy solo llegan como toast, se pierden si el usuario no está mirando la
     * pantalla exacta en el momento en que se disparan.
     */
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            // `json`, no `text` (el stub por defecto de Laravel): Filament filtra sus propias
            // notificaciones con `where('data->format', 'filament')`, que en PostgreSQL se traduce
            // al operador `->>` — exige una columna `json`/`jsonb`, falla con "operator does not
            // exist: text ->> unknown" sobre `text` (confirmado con un smoke test HTTP a /admin).
            $table->json('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
