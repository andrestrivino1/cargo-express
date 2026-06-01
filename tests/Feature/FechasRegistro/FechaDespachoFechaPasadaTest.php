<?php

namespace Tests\Feature\FechasRegistro;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FechaDespachoFechaPasadaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function escenario(): array
    {
        $despachador = User::factory()->create();
        $despachador->assignRole('despachador');

        $cliente = User::factory()->create();
        $cliente->assignRole('cliente');

        return [$despachador, $cliente];
    }

    #[Test]
    public function acepta_fecha_de_despacho_pasada(): void
    {
        [$despachador, $cliente] = $this->escenario();

        $this->actingAs($despachador)
            ->post(route('entregas.store'), [
                'cliente_id' => $cliente->id,
                'fecha_despacho' => now()->subDays(15)->format('Y-m-d'),
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('ordenes_cargue', [
            'cliente_id' => $cliente->id,
            'fecha_despacho' => now()->subDays(15)->startOfDay()->format('Y-m-d H:i:s'),
        ]);
    }

    #[Test]
    public function rechaza_fecha_de_despacho_vacia(): void
    {
        [$despachador, $cliente] = $this->escenario();

        $this->actingAs($despachador)
            ->post(route('entregas.store'), [
                'cliente_id' => $cliente->id,
                'fecha_despacho' => '',
            ])
            ->assertSessionHasErrors('fecha_despacho');
    }

    #[Test]
    public function rechaza_fecha_de_despacho_con_formato_invalido(): void
    {
        [$despachador, $cliente] = $this->escenario();

        $this->actingAs($despachador)
            ->post(route('entregas.store'), [
                'cliente_id' => $cliente->id,
                'fecha_despacho' => 'no-es-fecha',
            ])
            ->assertSessionHasErrors('fecha_despacho');
    }

    #[Test]
    public function sigue_aceptando_fecha_de_despacho_futura(): void
    {
        [$despachador, $cliente] = $this->escenario();

        $this->actingAs($despachador)
            ->post(route('entregas.store'), [
                'cliente_id' => $cliente->id,
                'fecha_despacho' => now()->addDays(5)->format('Y-m-d'),
            ])
            ->assertSessionHasNoErrors();
    }
}
