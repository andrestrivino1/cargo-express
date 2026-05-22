<?php

namespace Tests\Feature\Pendientes;

use App\Enums\ContenedorEstado;
use App\Enums\OrdenServicioEstado;
use App\Enums\SolicitudEstado;
use App\Models\Contenedor;
use App\Models\ImportBatch;
use App\Models\ImportPendingRecord;
use App\Models\OrdenServicio;
use App\Models\Solicitud;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CompletarRegistroTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['administrador', 'coordinador', 'cliente', 'portero'] as $r) {
            Role::findOrCreate($r, 'web');
        }
    }

    private function escenarioContenedorPendiente(): array
    {
        $admin = User::factory()->create();
        $admin->assignRole('coordinador');

        $cliente = User::factory()->create();
        $cliente->assignRole('cliente');

        $batch = ImportBatch::create([
            'usuario_id' => $admin->id,
            'archivo_nombre' => 'x.xlsx', 'archivo_hash' => str_repeat('a', 64), 'archivo_path' => 'x.xlsx',
            'modo' => 'importar', 'dry_run' => false, 'politica_duplicados' => 'omitir',
            'origen' => 'test', 'estado' => 'completado',
        ]);

        $sol = Solicitud::create([
            'cliente_id' => $cliente->id, 'numero_contenedor' => 'TEST123',
            'estado' => SolicitudEstado::Completada, 'fecha_solicitud' => now(), 'import_batch_id' => $batch->id,
        ]);
        $os = OrdenServicio::create([
            'solicitud_id' => $sol->id, 'coordinador_id' => $admin->id,
            'vehiculo' => 'PENDIENTE_HISTORICO', 'conductor' => 'PENDIENTE_HISTORICO',
            'cita_puerto' => now(), 'estado' => OrdenServicioEstado::Completada, 'import_batch_id' => $batch->id,
        ]);
        $cont = Contenedor::create([
            'orden_servicio_id' => $os->id, 'numero' => 'TEST123',
            'placa_vehiculo' => 'PENDIENTE_HISTORICO', 'estado' => ContenedorEstado::VaciadoCompletado,
            'fecha_ingreso' => now(), 'import_batch_id' => $batch->id,
        ]);
        ImportPendingRecord::create([
            'pendienteable_type' => Contenedor::class, 'pendienteable_id' => $cont->id,
            'import_batch_id' => $batch->id,
            'campos_pendientes' => ['placa_vehiculo', 'tipo', 'destino_salida'],
        ]);

        return [$admin, $cont];
    }

    #[Test]
    public function formulario_muestra_solo_los_campos_pendientes(): void
    {
        [$admin, $cont] = $this->escenarioContenedorPendiente();

        $this->actingAs($admin)
            ->get(route('pendientes.editar', ['type' => 'contenedor', 'id' => $cont->id]))
            ->assertOk()
            ->assertSee('placa_vehiculo')
            ->assertSee('Completar Contenedor');
    }

    #[Test]
    public function post_valido_marca_pendiente_completado_y_redirige(): void
    {
        [$admin, $cont] = $this->escenarioContenedorPendiente();

        $this->actingAs($admin)
            ->post(route('pendientes.actualizar', ['type' => 'contenedor', 'id' => $cont->id]), [
                'placa_vehiculo' => 'ABC-123',
                'tipo' => '40',
                'destino_salida' => 'Puerto Bvilla',
            ])
            ->assertRedirect(route('pendientes.index'));

        $cont->refresh();
        self::assertSame('ABC-123', $cont->placa_vehiculo);
        self::assertFalse($cont->tienePendientesImportacion());
    }

    #[Test]
    public function post_invalido_devuelve_errores(): void
    {
        [$admin, $cont] = $this->escenarioContenedorPendiente();

        $this->actingAs($admin)
            ->post(route('pendientes.actualizar', ['type' => 'contenedor', 'id' => $cont->id]), [
                'placa_vehiculo' => '', // requerido
            ])
            ->assertSessionHasErrors('placa_vehiculo');

        self::assertTrue($cont->fresh()->tienePendientesImportacion());
    }
}
