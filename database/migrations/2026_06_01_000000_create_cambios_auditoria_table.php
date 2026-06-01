<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cambios_auditoria', function (Blueprint $table) {
            $table->id();
            $table->morphs('auditable');
            $table->foreignId('usuario_id')->constrained('users');
            $table->json('cambios');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cambios_auditoria');
    }
};
