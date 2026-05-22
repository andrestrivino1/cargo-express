<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('photos', function (Blueprint $table) {
            $table->id();
            $table->string('photoable_type');
            $table->unsignedBigInteger('photoable_id');
            $table->string('ruta', 500);
            $table->string('nombre', 255);
            $table->unsignedInteger('tamaño');
            $table->timestamps();

            $table->index(['photoable_type', 'photoable_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('photos');
    }
};