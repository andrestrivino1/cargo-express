<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UbicacionPatioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $modules = ['A', 'B', 'C'];

        foreach ($modules as $module) {
            for ($position = 1; $position <= 10; $position++) {
                DB::table('ubicaciones_patio')->insert([
                    'modulo'      => $module,
                    'posicion'    => $position,
                    'descripcion' => "Módulo {$module} - Posición {$position}",
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
            }
        }
    }
}