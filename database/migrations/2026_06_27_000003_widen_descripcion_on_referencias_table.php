<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La descripción de una referencia importada se compone de Mercancía / #Referencia
 * / Detalle, y el detalle puede ser una lista larga de números de parte que supera
 * los 255 caracteres. En MySQL estricto (local) eso aborta la importación con error
 * 1406; en MariaDB no estricto (prod) se truncaba en silencio. Se amplía a TEXT
 * para preservar la descripción completa.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('referencias', function (Blueprint $table) {
            $table->text('descripcion')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('referencias', function (Blueprint $table) {
            $table->string('descripcion', 255)->nullable()->change();
        });
    }
};
