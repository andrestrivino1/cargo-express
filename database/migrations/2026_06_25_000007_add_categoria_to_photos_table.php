<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('photos', function (Blueprint $table) {
            // Categoría del archivo: bl, dim, lista_empaque, foto_mercancia, foto_conductor
            $table->string('categoria', 30)->nullable()->after('tipo');
        });
    }

    public function down(): void
    {
        Schema::table('photos', function (Blueprint $table) {
            $table->dropColumn('categoria');
        });
    }
};
