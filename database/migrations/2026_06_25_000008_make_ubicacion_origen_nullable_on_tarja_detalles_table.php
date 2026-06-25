<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tarja_detalles', function (Blueprint $table) {
            // El detalle de salida toma la ubicación de la referencia, que puede no tenerla.
            $table->foreignId('ubicacion_origen_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('tarja_detalles', function (Blueprint $table) {
            $table->foreignId('ubicacion_origen_id')->nullable(false)->change();
        });
    }
};
