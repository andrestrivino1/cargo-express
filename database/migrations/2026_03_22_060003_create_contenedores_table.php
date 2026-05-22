<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contenedores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orden_servicio_id')->constrained('ordenes_servicio');
            $table->string('numero', 20);
            $table->string('placa_vehiculo', 20)->nullable();
            $table->string('tipo', 50)->nullable();
            $table->string('estado', 30)->default('solicitado');
            $table->timestamp('fecha_ingreso')->nullable();
            $table->timestamp('fecha_salida')->nullable();
            $table->boolean('limpieza_registrada')->default(false);
            $table->string('destino_salida', 255)->nullable();
            $table->timestamps();

            $table->index('numero');
            $table->index('estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contenedores');
    }
};