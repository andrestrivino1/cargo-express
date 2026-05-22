<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Rollback: php artisan migrate:rollback --path=database/migrations/2026_05_21_100600_add_notas_conflicto_to_contenedores_table.php
// Añade columna para registrar conflictos detectados durante la importación
// (ej. mismo número de contenedor en hojas de 2 clientes distintos).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contenedores', function (Blueprint $table) {
            $table->text('notas_conflicto')->nullable()->after('destino_salida');
        });
    }

    public function down(): void
    {
        Schema::table('contenedores', function (Blueprint $table) {
            $table->dropColumn('notas_conflicto');
        });
    }
};
