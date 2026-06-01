<?php

namespace Tests\Feature\FechasRegistro;

use App\Enums\SolicitudEstado;
use App\Models\Solicitud;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CitaPuertoFechaPasadaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function escenario(): array
    {
        $coordinador = User::factory()->create();
        $coordinador->assignRole('coordinador');

        $cliente = User::factory()->create();
        $cliente->assignRole('cliente');

        $solicitud = Solicitud::create([
            'cliente_id' => $cliente->id,
            'numero_contenedor' => 'PASD123',
            'estado' => SolicitudEstado::Pendiente,
            'fecha_solicitud' => now(),
        ]);

        return [$coordinador, $solicitud];
    }

    #[Test]
    public function acepta_cita_en_puerto_con_fecha_pasada(): void
    {
        [$coordinador, $solicitud] = $this->escenario();
        $fechaPasada = now()->subDays(30)->format('Y-m-d\TH:i');

        $this->actingAs($coordinador)
            ->post(route('solicitudes.orden-servicio.store', $solicitud), [
                'vehiculo' => 'ABC123',
                'conductor' => 'Juan Perez',
                'cita_puerto' => $fechaPasada,
            ])
            ->assertRedirect(route('solicitudes.show', $solicitud))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('ordenes_servicio', [
            'solicitud_id' => $solicitud->id,
            'vehiculo' => 'ABC123',
        ]);

        self::assertSame(
            now()->subDays(30)->format('Y-m-d H:i'),
            $solicitud->fresh()->ordenServicio->cita_puerto->format('Y-m-d H:i'),
        );
    }

    #[Test]
    public function rechaza_cita_en_puerto_vacia(): void
    {
        [$coordinador, $solicitud] = $this->escenario();

        $this->actingAs($coordinador)
            ->post(route('solicitudes.orden-servicio.store', $solicitud), [
                'vehiculo' => 'ABC123',
                'conductor' => 'Juan Perez',
                'cita_puerto' => '',
            ])
            ->assertSessionHasErrors('cita_puerto');
    }

    #[Test]
    public function rechaza_cita_en_puerto_con_formato_invalido(): void
    {
        [$coordinador, $solicitud] = $this->escenario();

        $this->actingAs($coordinador)
            ->post(route('solicitudes.orden-servicio.store', $solicitud), [
                'vehiculo' => 'ABC123',
                'conductor' => 'Juan Perez',
                'cita_puerto' => 'no-es-fecha',
            ])
            ->assertSessionHasErrors('cita_puerto');
    }

    #[Test]
    public function sigue_aceptando_cita_en_puerto_futura(): void
    {
        [$coordinador, $solicitud] = $this->escenario();

        $this->actingAs($coordinador)
            ->post(route('solicitudes.orden-servicio.store', $solicitud), [
                'vehiculo' => 'ABC123',
                'conductor' => 'Juan Perez',
                'cita_puerto' => now()->addDays(5)->format('Y-m-d\TH:i'),
            ])
            ->assertRedirect(route('solicitudes.show', $solicitud))
            ->assertSessionHasNoErrors();
    }
}
