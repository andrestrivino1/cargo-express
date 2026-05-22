<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Rollback: php artisan migrate:rollback --path=database/migrations/2026_05_21_100100_create_import_batches_table.php
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('users');
            $table->string('archivo_nombre', 255);
            $table->char('archivo_hash', 64);
            $table->string('archivo_path', 500);
            $table->enum('modo', ['validar', 'importar']);
            $table->boolean('dry_run');
            $table->enum('politica_duplicados', ['omitir', 'actualizar_saldo', 'abortar'])->default('omitir');
            $table->date('fecha_corte')->nullable();
            $table->string('origen', 50)->default('carga_historica_27_02_2026');
            $table->enum('estado', ['pendiente', 'procesando', 'completado', 'fallido', 'cancelado'])->default('pendiente');

            $table->unsignedInteger('total_filas')->nullable();
            $table->unsignedInteger('importables')->nullable();
            $table->unsignedInteger('errores')->nullable();
            $table->unsignedInteger('advertencias')->nullable();
            $table->unsignedInteger('ignoradas')->nullable();
            $table->unsignedInteger('clientes_autocreados')->nullable();
            $table->unsignedInteger('contenedores_creados')->nullable();
            $table->unsignedInteger('referencias_creadas')->nullable();
            $table->unsignedInteger('despachos_historicos_creados')->nullable();

            $table->json('resumen')->nullable();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('finished_at')->nullable();
            $table->text('error_mensaje')->nullable();
            $table->timestamps();

            $table->index('usuario_id');
            $table->index('archivo_hash');
            $table->index('estado');
            $table->index('dry_run');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_batches');
    }
};
