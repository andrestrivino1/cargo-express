<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimientos_inventario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referencia_id')->constrained('referencias');
            $table->string('tipo', 20); // entrada | salida (MovimientoTipo)
            $table->unsignedInteger('cantidad');
            $table->unsignedInteger('saldo_resultante');
            $table->foreignId('usuario_id')->constrained('users');
            $table->nullableMorphs('documentable'); // ingreso (Contenedor) o salida (Tarja)
            $table->text('observaciones')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['referencia_id', 'created_at']);
            $table->index(['tipo', 'created_at']);
            $table->index('usuario_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos_inventario');
    }
};
