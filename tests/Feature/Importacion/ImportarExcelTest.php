<?php

namespace Tests\Feature\Importacion;

use App\Enums\ImportEstado;
use App\Jobs\ProcesarImportacionInventario;
use App\Models\Contenedor;
use App\Models\ImportBatch;
use App\Models\Ingreso;
use App\Models\MovimientoInventario;
use App\Models\Referencia;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ImportarExcelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['administrador', 'coordinador', 'cliente', 'despachador'] as $r) {
            Role::findOrCreate($r, 'web');
        }
        Storage::fake(config('importacion.disco'));
    }

    private function fixturePath(): string
    {
        $path = base_path('tests/Fixtures/inventario_minimo.xlsx');
        if (! file_exists($path)) {
            $this->markTestSkipped('Ejecutar php tests/Fixtures/generar_inventario_minimo.php');
        }

        return $path;
    }

    private function admin(): User
    {
        $a = User::factory()->create();
        $a->assignRole('administrador');

        return $a;
    }

    private function dispararImport(string $modo, array $extra = []): ImportBatch
    {
        $this->actingAs($this->admin())->post(route('importaciones.store'), array_merge([
            'archivo' => new UploadedFile($this->fixturePath(), 'inventario_minimo.xlsx', null, null, true),
            'modo' => $modo,
            'politica_duplicados' => 'omitir',
            'confirmar_clientes_autocreados' => '1',
        ], $extra));

        $batch = ImportBatch::query()->latest()->first();
        ProcesarImportacionInventario::dispatchSync($batch->id);

        return $batch->refresh();
    }

    #[Test]
    public function persistencia_crea_contenedores_referencias_y_clientes_autocreados(): void
    {
        $batch = $this->dispararImport('importar');

        self::assertSame(ImportEstado::Completado, $batch->estado, $batch->error_mensaje ?? '');
        self::assertGreaterThan(0, $batch->contenedores_creados);
        self::assertGreaterThan(0, $batch->referencias_creadas);
        self::assertGreaterThan(0, $batch->clientes_autocreados);

        $contenedoresEnBd = Contenedor::query()->where('import_batch_id', $batch->id)->count();
        self::assertSame((int) $batch->contenedores_creados, $contenedoresEnBd);

        // Cada cliente nuevo debe tener flags de primer login forzado
        $nuevosClientes = User::query()->where('import_batch_id_origen', $batch->id)->get();
        foreach ($nuevosClientes as $c) {
            self::assertTrue($c->requiere_cambio_password);
            self::assertTrue($c->email_placeholder);
        }
    }

    #[Test]
    public function cantidad_actual_es_el_valor_literal_del_excel_no_recalculado(): void
    {
        $batch = $this->dispararImport('importar');

        // En el fixture, la primera fila tiene unidad=10, despacho=3, inventario=7
        // Si recalculáramos sería 10-3=7 (igual). Probamos una fila donde difiera:
        // fila 3 del fixture: unidad=8, despacho=3, inventario=5 (10-3=7≠5)
        $ref = Referencia::query()
            ->where('codigo', 'REF-003')
            ->first();
        self::assertNotNull($ref);
        self::assertSame(5, (int) $ref->cantidad_actual, 'FR-030: cantidad_actual = inventario_fisico literal');
    }

    #[Test]
    public function cada_contenedor_creado_tiene_pendientes_vivos(): void
    {
        $batch = $this->dispararImport('importar');

        $contenedores = Contenedor::query()->where('import_batch_id', $batch->id)->get();
        self::assertNotEmpty($contenedores);

        foreach ($contenedores as $c) {
            self::assertTrue($c->tienePendientesImportacion(), "Contenedor {$c->numero} debería tener pendiente vivo");
        }
    }

    #[Test]
    public function importacion_se_integra_al_flujo_nuevo_ingreso_y_ledger(): void
    {
        $batch = $this->dispararImport('importar');

        self::assertSame(ImportEstado::Completado, $batch->estado, $batch->error_mensaje ?? '');

        // Cada contenedor importado cuelga de un Ingreso con BL provisional (por confirmar)
        $contenedores = Contenedor::query()->where('import_batch_id', $batch->id)->get();
        self::assertNotEmpty($contenedores);
        foreach ($contenedores as $c) {
            self::assertNotNull($c->ingreso_id, "Contenedor {$c->numero} sin ingreso_id");
        }

        $ingresos = Ingreso::query()->whereIn('id', $contenedores->pluck('ingreso_id'))->get();
        self::assertNotEmpty($ingresos);
        foreach ($ingresos as $i) {
            self::assertTrue((bool) $i->bl_por_confirmar, "Ingreso BL {$i->bl} debería estar 'por confirmar'");
        }

        // Cada referencia con saldo > 0 genera una entrada en el ledger
        $refsConSaldo = Referencia::query()->where('cantidad_actual', '>', 0)->count();
        $entradas = MovimientoInventario::query()->where('tipo', 'entrada')->count();
        self::assertSame($refsConSaldo, $entradas, 'Una entrada en el ledger por cada referencia con saldo');
    }

    #[Test]
    public function reimport_con_politica_omitir_no_crea_duplicados(): void
    {
        $batch1 = $this->dispararImport('importar');
        $totalContAntes = Contenedor::query()->count();

        $batch2 = $this->dispararImport('importar', ['politica_duplicados' => 'omitir']);

        $totalContDespues = Contenedor::query()->count();
        self::assertSame($totalContAntes, $totalContDespues, 'Idempotencia: reimport con omitir no crea duplicados');
    }
}
