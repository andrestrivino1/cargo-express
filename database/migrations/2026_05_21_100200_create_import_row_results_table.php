<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Rollback: php artisan migrate:rollback --path=database/migrations/2026_05_21_100200_create_import_row_results_table.php
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_row_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_batch_id')->constrained('import_batches')->cascadeOnDelete();
            $table->string('hoja', 120);
            $table->unsignedInteger('fila_excel');
            $table->enum('estado', ['importado', 'error', 'advertencia', 'ignorado']);
            $table->string('tipo', 60)->nullable();
            $table->text('mensaje');
            $table->foreignId('referencia_id')->nullable()->constrained('referencias')->nullOnDelete();
            $table->foreignId('contenedor_id')->nullable()->constrained('contenedores')->nullOnDelete();
            $table->foreignId('user_cliente_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('payload_original')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('import_batch_id');
            $table->index(['import_batch_id', 'estado']);
            $table->index(['import_batch_id', 'hoja']);
            $table->index('tipo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_row_results');
    }
};
