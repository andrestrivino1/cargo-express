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
        Schema::table('novedades', function (Blueprint $table) {
            $table->integer('cantidad_afectada')->nullable()->after('referencia_id');
        });
    }

    public function down(): void
    {
        Schema::table('novedades', function (Blueprint $table) {
            $table->dropColumn('cantidad_afectada');
        });
    }
};
