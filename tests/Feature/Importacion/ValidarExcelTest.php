<?php

namespace Tests\Feature\Importacion;

use App\Enums\ImportEstado;
use App\Models\Contenedor;
use App\Models\ImportBatch;
use App\Models\ImportRowResult;
use App\Models\Referencia;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ValidarExcelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::findOrCreate('administrador', 'web');
        Role::findOrCreate('cliente', 'web');
        Storage::fake(config('importacion.disco'));
    }

    private function fixturePath(): string
    {
        $path = base_path('tests/Fixtures/inventario_minimo.xlsx');
        if (! file_exists($path)) {
            $this->markTestSkipped('Fixture inventario_minimo.xlsx no existe. Ejecutar php tests/Fixtures/generar_inventario_minimo.php');
        }

        return $path;
    }

    #[Test]
    public function admin_puede_subir_excel_en_modo_validar_y_recibe_batch_pendiente(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('administrador');

        $archivo = new UploadedFile($this->fixturePath(), 'inventario_minimo.xlsx', null, null, true);

        $this->actingAs($admin)
            ->post(route('importaciones.store'), [
                'archivo' => $archivo,
                'modo' => 'validar',
                'fecha_corte' => '2026-02-27',
            ])
            ->assertRedirect();

        $batch = ImportBatch::query()->latest()->first();
        self::assertNotNull($batch);
        self::assertSame('validar', $batch->modo);
        self::assertTrue((bool) $batch->dry_run);
    }

    #[Test]
    public function dry_run_no_crea_entidades_operativas(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('administrador');
        $totalUsersAntes = User::query()->count();

        $archivo = new UploadedFile($this->fixturePath(), 'inventario_minimo.xlsx', null, null, true);

        $this->actingAs($admin)
            ->post(route('importaciones.store'), [
                'archivo' => $archivo,
                'modo' => 'validar',
            ]);

        // Procesar el job sincrónicamente.
        $batch = ImportBatch::query()->latest()->first();
        \App\Jobs\ProcesarImportacionInventario::dispatchSync($batch->id);

        $batch->refresh();
        self::assertSame(ImportEstado::Completado, $batch->estado, $batch->error_mensaje ?? '');

        // 100% de filas clasificadas
        self::assertGreaterThan(0, $batch->total_filas);
        self::assertSame(
            (int) $batch->total_filas,
            (int) (($batch->importables ?? 0) + ($batch->errores ?? 0) + ($batch->advertencias ?? 0) + ($batch->ignoradas ?? 0)),
            'SC-002: 100% de filas clasificadas'
        );

        // CERO escritura en tablas operativas
        self::assertSame(0, Contenedor::query()->count());
        self::assertSame(0, Referencia::query()->count());
        self::assertSame($totalUsersAntes, User::query()->count(), 'Dry-run no debe crear users');
    }

    #[Test]
    public function reporta_hojas_ignoradas_y_clientes_a_resolver(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('administrador');

        $archivo = new UploadedFile($this->fixturePath(), 'inventario_minimo.xlsx', null, null, true);

        $this->actingAs($admin)->post(route('importaciones.store'), [
            'archivo' => $archivo,
            'modo' => 'validar',
        ]);

        $batch = ImportBatch::query()->latest()->first();
        \App\Jobs\ProcesarImportacionInventario::dispatchSync($batch->id);
        $batch->refresh();

        // Hoja1 ignorada
        $ignoradas = ImportRowResult::where('import_batch_id', $batch->id)->where('estado', 'ignorado')->pluck('hoja');
        self::assertTrue($ignoradas->contains('Hoja1'));

        // Clientes a auto-crear listados en resumen
        $clientes = $batch->resumen['clientes_a_resolver'] ?? [];
        self::assertNotEmpty($clientes);
    }
}
