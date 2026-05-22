<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Rollback: php artisan migrate:rollback --path=database/migrations/2026_05_21_100300_create_import_pending_records_table.php
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_pending_records', function (Blueprint $table) {
            $table->id();
            $table->morphs('pendienteable');
            $table->foreignId('import_batch_id')->constrained('import_batches')->cascadeOnDelete();
            $table->json('campos_pendientes');
            $table->unsignedTinyInteger('prioridad')->default(50);
            $table->dateTime('completado_at')->nullable();
            $table->foreignId('completado_por_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('import_batch_id');
            $table->index('completado_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_pending_records');
    }
};
