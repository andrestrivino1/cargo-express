<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Rollback: php artisan migrate:rollback --path=database/migrations/2026_05_21_100500_add_pending_fields_to_tarjas_table.php
// Añade columnas necesarias para los campos PENDIENTE_HISTORICO de Tarja (US3).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tarjas', function (Blueprint $table) {
            $table->string('vehiculo', 20)->nullable()->after('observaciones');
            $table->string('conductor', 255)->nullable()->after('vehiculo');
        });
    }

    public function down(): void
    {
        Schema::table('tarjas', function (Blueprint $table) {
            $table->dropColumn(['vehiculo', 'conductor']);
        });
    }
};
