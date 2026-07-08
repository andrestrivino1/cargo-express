<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('idempotency_keys', function (Blueprint $table) {
            $table->id();
            $table->char('token', 36)->unique(); // UUID del intento — barrera atómica
            $table->string('scope', 40); // ámbito de la operación (p. ej. "salida")
            $table->foreignId('usuario_id')->constrained('users');
            $table->unsignedBigInteger('resource_id')->nullable(); // recurso creado (tarja)
            $table->timestamp('created_at')->useCurrent();

            $table->index(['scope', 'resource_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('idempotency_keys');
    }
};
