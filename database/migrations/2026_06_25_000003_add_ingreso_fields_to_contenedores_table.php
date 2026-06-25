<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contenedores', function (Blueprint $table) {
            $table->string('bl', 100)->nullable()->after('tipo');
            $table->string('tipo_mercancia', 100)->nullable()->after('bl');
            // El ingreso consolidado crea el contenedor sin pasar por Solicitud/OrdenServicio.
            $table->foreignId('orden_servicio_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('contenedores', function (Blueprint $table) {
            $table->dropColumn(['bl', 'tipo_mercancia']);
        });
    }
};
