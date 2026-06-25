<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tarjas', function (Blueprint $table) {
            $table->string('conductor_cedula', 20)->nullable()->after('conductor');
            $table->string('transportador', 150)->nullable()->after('conductor_cedula');
            $table->string('destino', 150)->nullable()->after('transportador');
            $table->unsignedInteger('consecutivo_odc')->nullable()->unique()->after('destino');
        });
    }

    public function down(): void
    {
        Schema::table('tarjas', function (Blueprint $table) {
            $table->dropColumn(['conductor_cedula', 'transportador', 'destino', 'consecutivo_odc']);
        });
    }
};
