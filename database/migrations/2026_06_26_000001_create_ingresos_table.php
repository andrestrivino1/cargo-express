<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ingresos', function (Blueprint $table) {
            $table->id();
            $table->string('bl', 100);
            $table->foreignId('cliente_id')->nullable()->constrained('users');
            $table->date('fecha_ingreso');
            $table->foreignId('usuario_id')->nullable()->constrained('users');
            $table->timestamps();

            $table->index('cliente_id');
            $table->index('fecha_ingreso');
            $table->index('bl');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ingresos');
    }
};
