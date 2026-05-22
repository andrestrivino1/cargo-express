<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ordenes_cargue', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('users');
            $table->foreignId('despachador_id')->nullable()->constrained('users');
            $table->date('fecha_despacho');
            $table->string('estado', 20)->default('pendiente');
            $table->text('notas')->nullable();
            $table->timestamps();

            $table->index('cliente_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ordenes_cargue');
    }
};
