<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tarjas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orden_cargue_id')->constrained('ordenes_cargue');
            $table->foreignId('despachador_id')->constrained('users');
            $table->timestamp('fecha_entrega');
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tarjas');
    }
};
