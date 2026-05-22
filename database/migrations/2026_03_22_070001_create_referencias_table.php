<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contenedor_id')->constrained('contenedores');
            $table->foreignId('cliente_id')->constrained('users');
            $table->string('codigo', 100);
            $table->string('descripcion', 255)->nullable();
            $table->unsignedInteger('cantidad_inicial');
            $table->unsignedInteger('cantidad_actual');
            $table->string('unidad_medida', 50)->nullable()->default('unidades');
            $table->foreignId('ubicacion_patio_id')->nullable()->constrained('ubicaciones_patio');
            $table->timestamp('fecha_ingreso');
            $table->timestamp('fecha_salida')->nullable();
            $table->timestamps();

            $table->index('contenedor_id');
            $table->index('cliente_id');
            $table->index('codigo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referencias');
    }
};