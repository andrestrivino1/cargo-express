<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('solicitudes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('users');
            $table->string('numero_contenedor', 20);
            $table->string('naviera', 100)->nullable();
            $table->string('puerto_origen', 100)->nullable();
            $table->text('descripcion')->nullable();
            $table->string('estado', 20)->default('pendiente');
            $table->timestamp('fecha_solicitud')->useCurrent();
            $table->timestamps();

            $table->index('cliente_id');
            $table->index('numero_contenedor');
            $table->index('estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitudes');
    }
};