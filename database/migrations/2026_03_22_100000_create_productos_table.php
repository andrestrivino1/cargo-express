<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 255);
            $table->string('medidas', 100)->nullable();
            $table->string('calibre', 50)->nullable();
            $table->decimal('peso', 10, 2)->nullable();
            $table->string('empaque', 100)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index('nombre');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};
