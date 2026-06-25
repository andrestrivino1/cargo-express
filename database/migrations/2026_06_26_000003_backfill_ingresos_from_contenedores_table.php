<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Compatibilidad (feature 005 → 006): por cada contenedor con BL y sin ingreso,
     * crea un Ingreso padre y lo vincula, para que esos ingresos de un solo
     * contenedor sigan apareciendo en el listado por BL. No elimina nada.
     */
    public function up(): void
    {
        $contenedores = DB::table('contenedores')
            ->whereNotNull('bl')
            ->whereNull('ingreso_id')
            ->get(['id', 'bl', 'fecha_ingreso']);

        foreach ($contenedores as $contenedor) {
            $clienteId = DB::table('referencias')
                ->where('contenedor_id', $contenedor->id)
                ->value('cliente_id');

            $fecha = $contenedor->fecha_ingreso
                ? substr((string) $contenedor->fecha_ingreso, 0, 10)
                : now()->toDateString();

            $ingresoId = DB::table('ingresos')->insertGetId([
                'bl' => $contenedor->bl,
                'cliente_id' => $clienteId,
                'fecha_ingreso' => $fecha,
                'usuario_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('contenedores')
                ->where('id', $contenedor->id)
                ->update(['ingreso_id' => $ingresoId]);
        }
    }

    public function down(): void
    {
        // Desvincula y elimina solo los ingresos creados por el backfill no es
        // determinístico; se deja sin reversa (los ingresos quedan como datos válidos).
    }
};
