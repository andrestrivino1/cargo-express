<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transferencias', function (Blueprint $table) {
            $table->id();
            $table->string('tipo', 20); // 'entre_modulos' o 'entre_clientes'
            $table->foreignId('usuario_id')->constrained('users');
            $table->foreignId('referencia_origen_id')->constrained('referencias');
            $table->foreignId('referencia_destino_id')->nullable()->constrained('referencias');
            $table->foreignId('ubicacion_origen_id')->constrained('ubicaciones_patio');
            $table->foreignId('ubicacion_destino_id')->constrained('ubicaciones_patio');
            $table->unsignedInteger('cantidad');
            $table->foreignId('cliente_origen_id')->nullable()->constrained('users');
            $table->foreignId('cliente_destino_id')->nullable()->constrained('users');
            $table->text('motivo')->nullable();
            $table->string('autorizacion_cliente', 255)->nullable();
            $table->timestamps();

            $table->index('tipo');
            $table->index('usuario_id');
            $table->index('referencia_origen_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transferencias');
    }
};
