<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cita: llegada física prevista de un contenedor que ya fue declarado en un
     * ingreso. La cadena operativa es Ingreso -> Cita -> Portería.
     */
    public function up(): void
    {
        Schema::create('citas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('ingreso_id')->constrained('ingresos')->cascadeOnDelete();
            $table->foreignId('contenedor_id')->constrained('contenedores')->cascadeOnDelete();

            // Copia normalizada del número de contenedor: permite que el portero
            // busque por índice sin depender de cómo se digitó.
            $table->string('numero_contenedor', 20);

            $table->string('tipo', 20);
            $table->string('tamano', 10);
            $table->string('condicion', 10);

            $table->date('fecha_esperada');
            $table->string('estado', 20)->default('programada');

            $table->string('conductor_nombre', 150);
            $table->string('conductor_cedula', 30);
            $table->string('conductor_cedula_original', 40)->nullable();
            $table->string('placa', 15);
            $table->string('placa_original', 20)->nullable();
            $table->string('empresa', 150);

            $table->foreignId('creado_por')->constrained('users');
            $table->foreignId('actualizado_por')->nullable()->constrained('users');

            $table->timestamps();

            // Consulta principal del portero: citas de hoy todavía programadas.
            $table->index(['fecha_esperada', 'estado']);
            // Búsqueda en puerta por placa o por contenedor.
            $table->index('placa');
            $table->index('numero_contenedor');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('citas');
    }
};
