<?php

namespace Database\Seeders;

use App\Models\Secuencia;
use Illuminate\Database\Seeder;

class SecuenciaOdcSeeder extends Seeder
{
    /**
     * Siembra la secuencia del consecutivo de la Orden de Salida (ODC).
     *
     * La muestra entregada es ODC-570 (ya emitida), por lo que el valor base
     * es 570 y el siguiente consecutivo emitido será 571.
     */
    public function run(): void
    {
        Secuencia::updateOrCreate(
            ['clave' => 'odc'],
            ['valor' => 570]
        );
    }
}
