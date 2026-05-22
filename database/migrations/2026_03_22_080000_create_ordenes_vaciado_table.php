<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ordenes_vaciado', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contenedor_id')->constrained('contenedores');
            $table->foreignId('supervisor_id')->constrained('users');
            $table->date('fecha_programada');
            $table->timestamp('fecha_inicio')->nullable();
            $table->timestamp('fecha_fin')->nullable();
            $table->string('estado', 20)->default('programada');
            $table->text('notas')->nullable();
            $table->timestamps();

            $table->index('contenedor_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ordenes_vaciado');
    }
};