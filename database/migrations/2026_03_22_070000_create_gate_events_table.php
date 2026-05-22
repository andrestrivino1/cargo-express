<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gate_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contenedor_id')->constrained('contenedores');
            $table->string('tipo', 20);
            $table->foreignId('usuario_id')->constrained('users');
            $table->timestamp('hora');
            $table->text('estado_fisico')->nullable();
            $table->text('notas')->nullable();
            $table->timestamps();

            $table->index('contenedor_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gate_events');
    }
};