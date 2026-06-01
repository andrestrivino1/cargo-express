<?php

namespace Tests\Feature\FechasRegistro;

use App\Enums\ContenedorEstado;
use App\Enums\OrdenServicioEstado;
use App\Enums\SolicitudEstado;
use App\Models\Contenedor;
use App\Models\OrdenServicio;
use App\Models\Solicitud;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FechaVaciadoFechaPasadaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function contenedor(ContenedorEstado $estado): Contenedor
    {
        $cliente = User::factory()->create();
        $cliente->assignRole('cliente');

        $coordinador = User::factory()->create();
        $coordinador->assignRole('coordinador');

        $solicitud = Solicitud::create([
            'cliente_id' => $cliente->id,
            'numero_contenedor' => 'VAC123',
            'estado' => SolicitudEstado::Asignada,
            'fecha_solicitud' => now(),
        ]);

        $ordenServicio = OrdenServicio::create([
            'solicitud_id' => $solicitud->id,
            'coordinador_id' => $coordinador->id,
            'vehiculo' => 'ABC123',
            'conductor' => 'Juan Perez',
            'cita_puerto' => now(),
            'estado' => OrdenServicioEstado::Completada,
        ]);

        return Contenedor::create([
            'orden_servicio_id' => $ordenServicio->id,
            'numero' => 'VAC123',
            'estado' => $estado,
            'fecha_ingreso' => now(),
        ]);
    }

    private function supervisor(): User
    {
        $supervisor = User::factory()->create();
        $supervisor->assignRole('supervisor');

        return $supervisor;
    }

    #[Test]
    public function acepta_fecha_programada_pasada_con_contenedor_en_patio(): void
    {
        $contenedor = $this->contenedor(ContenedorEstado::EnPatio);

        $this->actingAs($this->supervisor())
            ->post(route('vaciado.store'), [
                'contenedor_id' => $contenedor->id,
                'fecha_programada' => now()->subDays(20)->format('Y-m-d'),
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('ordenes_vaciado', [
            'contenedor_id' => $contenedor->id,
            'fecha_programada' => now()->subDays(20)->startOfDay()->format('Y-m-d H:i:s'),
        ]);
    }

    #[Test]
    public function rechaza_fecha_programada_vacia(): void
    {
        $contenedor = $this->contenedor(ContenedorEstado::EnPatio);

        $this->actingAs($this->supervisor())
            ->post(route('vaciado.store'), [
                'contenedor_id' => $contenedor->id,
                'fecha_programada' => '',
            ])
            ->assertSessionHasErrors('fecha_programada');
    }

    #[Test]
    public function rechaza_fecha_programada_con_formato_invalido(): void
    {
        $contenedor = $this->contenedor(ContenedorEstado::EnPatio);

        $this->actingAs($this->supervisor())
            ->post(route('vaciado.store'), [
                'contenedor_id' => $contenedor->id,
                'fecha_programada' => 'no-es-fecha',
            ])
            ->assertSessionHasErrors('fecha_programada');
    }

    #[Test]
    public function sigue_aceptando_fecha_programada_futura(): void
    {
        $contenedor = $this->contenedor(ContenedorEstado::EnPatio);

        $this->actingAs($this->supervisor())
            ->post(route('vaciado.store'), [
                'contenedor_id' => $contenedor->id,
                'fecha_programada' => now()->addDays(5)->format('Y-m-d'),
            ])
            ->assertSessionHasNoErrors();
    }

    #[Test]
    public function mantiene_validacion_de_contenedor_no_en_patio(): void
    {
        $contenedor = $this->contenedor(ContenedorEstado::VaciadoCompletado);

        $this->actingAs($this->supervisor())
            ->post(route('vaciado.store'), [
                'contenedor_id' => $contenedor->id,
                'fecha_programada' => now()->subDays(20)->format('Y-m-d'),
            ])
            ->assertSessionHasErrors('contenedor_id');
    }
}
