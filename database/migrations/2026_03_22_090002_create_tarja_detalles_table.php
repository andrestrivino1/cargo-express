<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tarja_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tarja_id')->constrained('tarjas');
            $table->foreignId('referencia_id')->constrained('referencias');
            $table->unsignedInteger('cantidad_entregada');
            $table->foreignId('ubicacion_origen_id')->constrained('ubicaciones_patio');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tarja_detalles');
    }
};
