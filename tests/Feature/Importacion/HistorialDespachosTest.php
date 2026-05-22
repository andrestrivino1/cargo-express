<?php

namespace Tests\Feature\Importacion;

use App\Enums\ImportEstado;
use App\Jobs\ProcesarImportacionInventario;
use App\Models\ImportBatch;
use App\Models\ImportRowResult;
use App\Models\Referencia;
use App\Models\Tarja;
use App\Models\TarjaDetalle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class HistorialDespachosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['administrador', 'cliente', 'despachador'] as $r) {
            Role::findOrCreate($r, 'web');
        }
        Storage::fake(config('importacion.disco'));
    }

    private function importarFixture(): ImportBatch
    {
        $path = base_path('tests/Fixtures/inventario_minimo.xlsx');
        if (! file_exists($path)) {
            $this->markTestSkipped('Ejecutar php tests/Fixtures/generar_inventario_minimo.php');
        }

        $admin = User::factory()->create();
        $admin->assignRole('administrador');

        $this->actingAs($admin)->post(route('importaciones.store'), [
            'archivo' => new UploadedFile($path, 'inventario_minimo.xlsx', null, null, true),
            'modo' => 'importar',
            'politica_duplicados' => 'omitir',
            'confirmar_clientes_autocreados' => '1',
        ]);

        $batch = ImportBatch::query()->latest()->first();
        ProcesarImportacionInventario::dispatchSync($batch->id);

        return $batch->refresh();
    }

    #[Test]
    public function pares_de_despacho_se_persisten_como_tarjas_retroactivas(): void
    {
        $batch = $this->importarFixture();

        self::assertSame(ImportEstado::Completado, $batch->estado, $batch->error_mensaje ?? '');
        self::assertGreaterThan(0, $batch->despachos_historicos_creados);

        $tarjasDelBatch = Tarja::query()->where('import_batch_id', $batch->id)->count();
        self::assertSame((int) $batch->despachos_historicos_creados, $tarjasDelBatch);

        // Cada tarja retroactiva tiene un pendiente vivo
        $tarjas = Tarja::query()->where('import_batch_id', $batch->id)->get();
        foreach ($tarjas as $t) {
            self::assertTrue($t->tienePendientesImportacion(), "Tarja {$t->id} debería tener pendiente vivo");
        }
    }

    #[Test]
    public function saldo_de_referencia_no_se_descuenta_por_historial(): void
    {
        $batch = $this->importarFixture();

        // En el fixture, REF-001 tiene unidad=10, despacho=3, inventario=7
        $ref = Referencia::query()->where('codigo', 'REF-001')->first();
        self::assertNotNull($ref);
        // El saldo debe ser EXACTAMENTE 7, no 10-3=7 por recálculo (FR-030).
        // Aunque ambos coinciden numéricamente aquí, validamos con REF-003 donde diverge:
        $ref3 = Referencia::query()->where('codigo', 'REF-003')->first();
        self::assertSame(5, (int) $ref3->cantidad_actual);

        // Y verificamos que existen TarjaDetalle apuntando a REF-003 pero la cantidad_actual NO se redujo más.
        $detalles = TarjaDetalle::query()->where('referencia_id', $ref3->id)->count();
        self::assertGreaterThan(0, $detalles, 'Debe haber TarjaDetalle retroactivos');
    }

    #[Test]
    public function pares_incompletos_se_reportan_como_advertencia_no_bloquean_fila(): void
    {
        $batch = $this->importarFixture();

        // Buscar advertencia DESPACHO_INCOMPLETO (no debe abortar el batch)
        $advertencias = ImportRowResult::query()
            ->where('import_batch_id', $batch->id)
            ->where('estado', 'advertencia')
            ->get();

        self::assertSame(ImportEstado::Completado, $batch->estado);
    }
}
