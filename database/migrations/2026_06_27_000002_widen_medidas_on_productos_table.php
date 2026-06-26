<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * El importador usa `medidas` como detalle libre del producto (parte de su
 * identidad junto al nombre). Algunos archivos traen listas largas de números de
 * parte que superan los 100 caracteres originales: en MySQL estricto (local) eso
 * aborta la importación con error 1406, mientras en MariaDB no estricto (prod) se
 * truncaba en silencio. Se amplía a TEXT para preservar el dato completo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->text('medidas')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->string('medidas', 100)->nullable()->change();
        });
    }
};
