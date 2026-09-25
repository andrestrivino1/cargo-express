<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Feature 010 / US2 — retiro de referencias del inventario vigente.
 *
 * Se usa el soft delete de Laravel en vez de una bandera propia porque hay ~20
 * puntos distintos que consultan `referencias` (inventario, exportables,
 * reportes, salida, transferencias, vaciado, entregas, dashboard). Con una
 * bandera habría que filtrar en cada uno y basta olvidar uno para que mercancía
 * retirada reaparezca en el exportable de un cliente o siga siendo seleccionable
 * en una orden de salida.
 *
 * `retirado_por` queda nulable: las filas anteriores a esta feature no tienen
 * retiro que registrar. El retiro no pide motivo —la constancia es quién lo hizo
 * y cuándo (`deleted_at`)—, así que no hay columna para explicarlo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('referencias', function (Blueprint $table) {
            $table->softDeletes();
            $table->foreignId('retirado_por')->nullable()->after('fecha_salida')->constrained('users');
        });
    }

    public function down(): void
    {
        Schema::table('referencias', function (Blueprint $table) {
            $table->dropConstrainedForeignId('retirado_por');
            $table->dropSoftDeletes();
        });
    }
};
