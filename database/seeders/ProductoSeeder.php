<?php

namespace Database\Seeders;

use App\Models\Producto;
use Illuminate\Database\Seeder;

class ProductoSeeder extends Seeder
{
    public function run(): void
    {
        $productos = [
            [
                'nombre' => 'Tubería PVC',
                'medidas' => '4 pulgadas x 6m',
                'calibre' => 'Schedule 40',
                'peso' => 12.5,
                'empaque' => 'Atado',
                'activo' => true,
            ],
            [
                'nombre' => 'Lámina de Acero',
                'medidas' => '1.22m x 2.44m',
                'calibre' => 'Calibre 18',
                'peso' => 45.0,
                'empaque' => 'Paquete',
                'activo' => true,
            ],
            [
                'nombre' => 'Cable Eléctrico',
                'medidas' => '100m',
                'calibre' => 'AWG 12',
                'peso' => 8.0,
                'empaque' => 'Rollo',
                'activo' => true,
            ],
            [
                'nombre' => 'Cemento Portland',
                'medidas' => '50kg',
                'calibre' => null,
                'peso' => 50.0,
                'empaque' => 'Saco',
                'activo' => true,
            ],
            [
                'nombre' => 'Varilla Corrugada',
                'medidas' => '3/8 pulgada x 12m',
                'calibre' => 'Grado 60',
                'peso' => 6.7,
                'empaque' => 'Atado',
                'activo' => true,
            ],
        ];

        foreach ($productos as $producto) {
            Producto::create($producto);
        }
    }
}
