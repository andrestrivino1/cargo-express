<?php

namespace Database\Factories;

use App\Enums\ImportEstado;
use App\Models\ImportBatch;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ImportBatch>
 */
class ImportBatchFactory extends Factory
{
    protected $model = ImportBatch::class;

    public function definition(): array
    {
        return [
            'usuario_id' => User::factory(),
            'archivo_nombre' => 'inventario_test.xlsx',
            'archivo_hash' => str_repeat('a', 64),
            'archivo_path' => 'test/'.fake()->uuid().'.xlsx',
            'modo' => 'validar',
            'dry_run' => true,
            'politica_duplicados' => 'omitir',
            'fecha_corte' => '2026-02-27',
            'origen' => 'test',
            'estado' => ImportEstado::Pendiente,
        ];
    }

    public function importar(): static
    {
        return $this->state(['modo' => 'importar', 'dry_run' => false]);
    }

    public function completado(): static
    {
        return $this->state([
            'estado' => ImportEstado::Completado,
            'started_at' => now()->subMinutes(5),
            'finished_at' => now(),
            'total_filas' => 100,
            'importables' => 95,
            'errores' => 5,
        ]);
    }
}
