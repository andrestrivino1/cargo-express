<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('referencias', function (Blueprint $table) {
            $table->foreignId('producto_id')->nullable()->after('contenedor_id')->constrained('productos');
            $table->index('producto_id');
        });
    }

    public function down(): void
    {
        Schema::table('referencias', function (Blueprint $table) {
            $table->dropForeign(['producto_id']);
            $table->dropIndex(['producto_id']);
            $table->dropColumn('producto_id');
        });
    }
};
