<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Rollback: php artisan migrate:rollback --path=database/migrations/2026_05_21_100400_add_import_batch_id_to_operational_tables.php
return new class extends Migration
{
    private array $operationalTables = [
        'solicitudes',
        'ordenes_servicio',
        'contenedores',
        'ordenes_cargue',
        'tarjas',
    ];

    public function up(): void
    {
        foreach ($this->operationalTables as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->foreignId('import_batch_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('import_batches')
                    ->nullOnDelete();

                $table->index('import_batch_id');
            });
        }

        // FK retroactiva en users.import_batch_id_origen (la columna ya existe desde T005).
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('import_batch_id_origen')
                ->references('id')
                ->on('import_batches')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['import_batch_id_origen']);
        });

        foreach ($this->operationalTables as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->dropForeign(['import_batch_id']);
                $table->dropIndex(['import_batch_id']);
                $table->dropColumn('import_batch_id');
            });
        }
    }
};
