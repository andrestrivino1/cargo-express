<?php

namespace Tests\Feature\Edicion;

use App\Enums\SolicitudEstado;
use App\Models\Solicitud;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EditarSolicitudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function solicitud(): Solicitud
    {
        $cliente = User::factory()->create();
        $cliente->assignRole('cliente');

        return Solicitud::create([
            'cliente_id' => $cliente->id,
            'numero_contenedor' => 'ORIG123',
            'naviera' => 'NavieraVieja',
            'estado' => SolicitudEstado::Pendiente,
            'fecha_solicitud' => now()->startOfDay(),
        ]);
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole('administrador');

        return $admin;
    }

    #[Test]
    public function administrador_edita_una_solicitud_y_queda_auditado(): void
    {
        $solicitud = $this->solicitud();
        $estadoOriginal = $solicitud->estado;

        $this->actingAs($this->admin())
            ->put(route('solicitudes.update', $solicitud), [
                'cliente_id' => $solicitud->cliente_id,
                'numero_contenedor' => 'NUEVO999',
                'naviera' => 'NavieraNueva',
                'fecha_solicitud' => $solicitud->fecha_solicitud->format('Y-m-d'),
            ])
            ->assertRedirect(route('solicitudes.show', $solicitud))
            ->assertSessionHasNoErrors();

        $solicitud->refresh();
        self::assertSame('NUEVO999', $solicitud->numero_contenedor);
        self::assertSame('NavieraNueva', $solicitud->naviera);
        // El estado no cambia por editar otros campos (FR-010)
        self::assertSame($estadoOriginal, $solicitud->estado);

        $this->assertDatabaseHas('cambios_auditoria', [
            'auditable_type' => Solicitud::class,
            'auditable_id' => $solicitud->id,
        ]);
        self::assertCount(1, $solicitud->cambiosAuditoria);
    }

    #[Test]
    public function rechaza_edicion_invalida_y_conserva_el_valor_anterior(): void
    {
        $solicitud = $this->solicitud();

        $this->actingAs($this->admin())
            ->put(route('solicitudes.update', $solicitud), [
                'cliente_id' => $solicitud->cliente_id,
                'numero_contenedor' => '', // requerido
                'fecha_solicitud' => $solicitud->fecha_solicitud->format('Y-m-d'),
            ])
            ->assertSessionHasErrors('numero_contenedor');

        self::assertSame('ORIG123', $solicitud->fresh()->numero_contenedor);
        $this->assertDatabaseCount('cambios_auditoria', 0);
    }

    #[Test]
    public function un_rol_no_autorizado_no_puede_editar(): void
    {
        $solicitud = $this->solicitud();
        $portero = User::factory()->create();
        $portero->assignRole('portero');

        $this->actingAs($portero)
            ->get(route('solicitudes.editar', $solicitud))
            ->assertForbidden();

        $this->actingAs($portero)
            ->put(route('solicitudes.update', $solicitud), [
                'cliente_id' => $solicitud->cliente_id,
                'numero_contenedor' => 'HACK',
                'fecha_solicitud' => $solicitud->fecha_solicitud->format('Y-m-d'),
            ])
            ->assertForbidden();

        self::assertSame('ORIG123', $solicitud->fresh()->numero_contenedor);
    }

    #[Test]
    public function guardar_sin_cambios_no_genera_auditoria(): void
    {
        $solicitud = $this->solicitud();

        $this->actingAs($this->admin())
            ->put(route('solicitudes.update', $solicitud), [
                'cliente_id' => $solicitud->cliente_id,
                'numero_contenedor' => $solicitud->numero_contenedor,
                'naviera' => $solicitud->naviera,
                'fecha_solicitud' => $solicitud->fecha_solicitud->format('Y-m-d'),
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('cambios_auditoria', 0);
    }
}
