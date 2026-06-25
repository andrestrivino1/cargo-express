<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contenedores', function (Blueprint $table) {
            $table->foreignId('ingreso_id')->nullable()->after('id')->constrained('ingresos');
        });
    }

    public function down(): void
    {
        Schema::table('contenedores', function (Blueprint $table) {
            $table->dropConstrainedForeignId('ingreso_id');
        });
    }
};
