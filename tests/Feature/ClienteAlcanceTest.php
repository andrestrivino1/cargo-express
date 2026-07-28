<?php

namespace Tests\Feature;

use App\Models\Contenedor;
use App\Models\Ingreso;
use App\Models\Referencia;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Aislamiento entre clientes.
 *
 * Antes de esta feature, AlmacenamientoController tomaba `cliente_id` del request
 * sin verificar propiedad: un cliente autenticado podía ver el inventario de otro
 * cambiando el parámetro en la URL. Estos tests cierran esa puerta.
 */
class ClienteAlcanceTest extends TestCase
{
    use RefreshDatabase;

    private function cliente(string $nombre): User
    {
        $user = User::factory()->create(['name' => $nombre]);
        $user->assignRole('cliente');

        return $user;
    }

    private function mercanciaDe(User $cliente, string $codigo): Referencia
    {
        $ingreso = Ingreso::create([
            'bl' => 'BL-'.$codigo,
            'cliente_id' => $cliente->id,
            'fecha_ingreso' => today()->subDays(3),
            'usuario_id' => $cliente->id,
        ]);

        $contenedor = $ingreso->contenedores()->create([
            'numero' => 'CONT'.$codigo,
            'tipo_mercancia' => 'General',
            'bl' => $ingreso->bl,
            'estado' => 'en_patio',
            'fecha_ingreso' => today()->subDays(3),
        ]);

        return $contenedor->referencias()->create([
            'cliente_id' => $cliente->id,
            'codigo' => $codigo,
            'descripcion' => 'Mercancía '.$codigo,
            'cantidad_inicial' => 10,
            'cantidad_actual' => 10,
            'unidad_medida' => 'caja',
            'fecha_ingreso' => today()->subDays(3),
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_cliente_solo_ve_su_propia_mercancia(): void
    {
        $clienteA = $this->cliente('Cliente A');
        $clienteB = $this->cliente('Cliente B');
        $this->mercanciaDe($clienteA, 'REFA001');
        $this->mercanciaDe($clienteB, 'REFB002');

        $this->actingAs($clienteA)->get(route('inventario.index'))
            ->assertOk()
            ->assertSee('REFA001')
            ->assertDontSee('REFB002');
    }

    public function test_manipular_cliente_id_en_la_url_no_expone_mercancia_ajena(): void
    {
        $clienteA = $this->cliente('Cliente A');
        $clienteB = $this->cliente('Cliente B');
        $this->mercanciaDe($clienteA, 'REFA001');
        $this->mercanciaDe($clienteB, 'REFB002');

        // El cliente A pide explícitamente el inventario del cliente B.
        $this->actingAs($clienteA)
            ->get(route('inventario.index', ['cliente_id' => $clienteB->id]))
            ->assertOk()
            ->assertSee('REFA001')
            ->assertDontSee('REFB002');
    }

    public function test_la_exportacion_a_excel_respeta_el_alcance(): void
    {
        $clienteA = $this->cliente('Cliente A');
        $clienteB = $this->cliente('Cliente B');
        $this->mercanciaDe($clienteA, 'REFA001');
        $this->mercanciaDe($clienteB, 'REFB002');

        // Se comprueba sobre la consulta del export, no sobre el binario del
        // archivo: es lo que determina qué filas salen.
        $export = app(\App\Services\InventarioService::class)
            ->exportarInventario(['cliente_id' => $clienteB->id], $clienteA);

        $codigos = $export->query()->pluck('codigo')->all();

        $this->assertSame(['REFA001'], $codigos);
    }

    public function test_la_exportacion_pdf_responde_y_no_falla_para_un_cliente(): void
    {
        $clienteA = $this->cliente('Cliente A');
        $clienteB = $this->cliente('Cliente B');
        $this->mercanciaDe($clienteA, 'REFA001');
        $this->mercanciaDe($clienteB, 'REFB002');

        $this->actingAs($clienteA)
            ->get(route('inventario.export.pdf', ['cliente_id' => $clienteB->id]))
            ->assertOk();
    }

    public function test_el_servicio_fuerza_el_alcance_del_cliente(): void
    {
        $clienteA = $this->cliente('Cliente A');
        $clienteB = $this->cliente('Cliente B');

        $servicio = app(\App\Services\InventarioService::class);

        $filtros = $servicio->filtrosConAlcance(['cliente_id' => $clienteB->id], $clienteA);

        $this->assertSame($clienteA->id, $filtros['cliente_id']);
    }

    public function test_el_alcance_no_afecta_a_usuarios_internos(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('administrador');
        $clienteB = $this->cliente('Cliente B');

        $servicio = app(\App\Services\InventarioService::class);

        $filtros = $servicio->filtrosConAlcance(['cliente_id' => $clienteB->id], $admin);

        // Un administrador sí puede filtrar por el cliente que quiera.
        $this->assertSame($clienteB->id, $filtros['cliente_id']);
    }

    public function test_cliente_sin_mercancia_ve_mensaje_explicito(): void
    {
        $cliente = $this->cliente('Cliente Vacío');

        $this->actingAs($cliente)->get(route('inventario.index'))
            ->assertOk()
            ->assertSee('Sin mercancía almacenada');
    }

    public function test_cliente_no_accede_a_reportes_ni_trazabilidad_ni_entregas(): void
    {
        $cliente = $this->cliente('Cliente A');

        $this->actingAs($cliente)->get(route('reportes.index'))->assertForbidden();
        $this->actingAs($cliente)->get(route('trazabilidad.index'))->assertForbidden();
        $this->actingAs($cliente)->get(route('ingreso.index'))->assertForbidden();
        $this->actingAs($cliente)->get(route('salida.index'))->assertForbidden();
    }
}
