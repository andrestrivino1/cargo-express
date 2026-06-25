<?php

namespace Tests\Feature;

use App\Enums\ContenedorEstado;
use App\Models\Contenedor;
use App\Models\Referencia;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportesOperativosTest extends TestCase
{
    use RefreshDatabase;

    private function supervisor(): User
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('supervisor');

        return $user;
    }

    public function test_inventario_por_cliente_refleja_saldos(): void
    {
        $supervisor = $this->supervisor();
        $cliente = User::factory()->create(['name' => 'CRISTALES DE COLOMBIA']);
        $contenedor = Contenedor::create(['numero' => 'C1', 'estado' => ContenedorEstado::EnPatio, 'fecha_ingreso' => now()]);
        Referencia::create([
            'contenedor_id' => $contenedor->id, 'cliente_id' => $cliente->id, 'codigo' => 'r1',
            'cantidad_inicial' => 10, 'cantidad_actual' => 7, 'unidad_medida' => 'unidades', 'fecha_ingreso' => now(),
        ]);

        $response = $this->actingAs($supervisor)->get(route('reportes.inventario-por-cliente'));

        $response->assertOk();
        $response->assertSee('CRISTALES DE COLOMBIA');
        $response->assertSee('7');
    }

    public function test_reportes_de_movimientos_responden(): void
    {
        $supervisor = $this->supervisor();

        foreach (['reportes.ingresos', 'reportes.salidas', 'reportes.movimientos', 'reportes.novedades', 'reportes.evidencias'] as $ruta) {
            $this->actingAs($supervisor)->get(route($ruta))->assertOk();
        }
    }
}
