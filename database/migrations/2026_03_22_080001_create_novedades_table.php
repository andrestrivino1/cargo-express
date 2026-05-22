<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('novedades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orden_vaciado_id')->constrained('ordenes_vaciado');
            $table->foreignId('operador_id')->constrained('users');
            $table->string('tipo', 20);
            $table->text('descripcion');
            $table->foreignId('referencia_id')->nullable()->constrained('referencias');
            $table->timestamps();

            $table->index('orden_vaciado_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('novedades');
    }
};