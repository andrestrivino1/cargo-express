<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ingresos', function (Blueprint $table) {
            // true en ingresos creados por importación con BL provisional (= número
            // de contenedor). El usuario edita el ingreso, pone el BL real y se limpia.
            $table->boolean('bl_por_confirmar')->default(false)->after('bl');
        });
    }

    public function down(): void
    {
        Schema::table('ingresos', function (Blueprint $table) {
            $table->dropColumn('bl_por_confirmar');
        });
    }
};
