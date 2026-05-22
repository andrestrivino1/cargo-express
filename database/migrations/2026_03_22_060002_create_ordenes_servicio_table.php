<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ordenes_servicio', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solicitud_id')->constrained('solicitudes')->unique();
            $table->foreignId('coordinador_id')->constrained('users');
            $table->string('vehiculo', 20);
            $table->string('conductor', 255);
            $table->string('conductor_documento', 20)->nullable();
            $table->dateTime('cita_puerto');
            $table->string('estado', 20)->default('activa');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ordenes_servicio');
    }
};