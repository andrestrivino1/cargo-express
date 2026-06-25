<?php

namespace Tests\Feature;

use App\Enums\ContenedorEstado;
use App\Models\Contenedor;
use App\Models\MovimientoInventario;
use App\Models\Referencia;
use App\Models\Tarja;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SecuenciaOdcSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SalidaMercanciaTest extends TestCase
{
    use RefreshDatabase;

    private function escenario(int $saldo = 10): array
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(SecuenciaOdcSeeder::class);

        $despachador = User::factory()->create();
        $despachador->assignRole('despachador');

        $cliente = User::factory()->create(['nit' => '9017949782']);
        $contenedor = Contenedor::create([
            'numero' => 'MEDU5858891',
            'estado' => ContenedorEstado::EnPatio,
            'fecha_ingreso' => now(),
        ]);
        $ref = Referencia::create([
            'contenedor_id' => $contenedor->id,
            'cliente_id' => $cliente->id,
            'codigo' => 'bronce 4mm',
            'descripcion' => 'Bronce 4mm',
            'cantidad_inicial' => $saldo,
            'cantidad_actual' => $saldo,
            'unidad_medida' => 'unidades',
            'fecha_ingreso' => now(),
        ]);

        return compact('despachador', 'cliente', 'ref');
    }

    private function payload(int $clienteId, int $refId, int $cantidad): array
    {
        return [
            'cliente_id' => $clienteId,
            'fecha_salida' => now()->format('Y-m-d'),
            'conductor' => 'Wilmer Arango',
            'conductor_cedula' => '123456',
            'placa_vehiculo' => 'NQL-738',
            'transportador' => 'El Triunfo',
            'destino' => 'Cali',
            'observaciones' => 'Sin novedad',
            'detalles' => [
                ['referencia_id' => $refId, 'cantidad' => $cantidad],
            ],
            'foto_mercancia' => UploadedFile::fake()->image('mercancia.jpg'),
            'foto_conductor' => UploadedFile::fake()->image('conductor.jpg'),
        ];
    }

    public function test_salida_descuenta_inventario_y_genera_consecutivo(): void
    {
        Storage::fake('public');
        ['despachador' => $d, 'cliente' => $c, 'ref' => $ref] = $this->escenario(10);

        $response = $this->actingAs($d)->post(route('salida.store'), $this->payload($c->id, $ref->id, 4));

        $response->assertRedirect();
        $ref->refresh();
        $this->assertSame(6, $ref->cantidad_actual);

        $tarja = Tarja::first();
        $this->assertSame(571, $tarja->consecutivo_odc); // 570 + 1
        $mov = MovimientoInventario::where('tipo', 'salida')->first();
        $this->assertSame(4, $mov->cantidad);
        $this->assertSame(6, $mov->saldo_resultante);
        $this->assertSame($d->id, $mov->usuario_id);
    }

    public function test_salida_excede_saldo_es_rechazada(): void
    {
        Storage::fake('public');
        ['despachador' => $d, 'cliente' => $c, 'ref' => $ref] = $this->escenario(5);

        $response = $this->actingAs($d)->post(route('salida.store'), $this->payload($c->id, $ref->id, 9));

        $response->assertSessionHasErrors('detalles');
        $ref->refresh();
        $this->assertSame(5, $ref->cantidad_actual); // sin cambios
        $this->assertSame(0, Tarja::count()); // transacción revertida
    }

    public function test_salida_sin_fotos_falla(): void
    {
        ['despachador' => $d, 'cliente' => $c, 'ref' => $ref] = $this->escenario(10);
        $data = $this->payload($c->id, $ref->id, 1);
        unset($data['foto_mercancia'], $data['foto_conductor']);

        $response = $this->actingAs($d)->post(route('salida.store'), $data);

        $response->assertSessionHasErrors(['foto_mercancia', 'foto_conductor']);
    }

    public function test_invariante_saldo_igual_entradas_menos_salidas(): void
    {
        Storage::fake('public');
        ['despachador' => $d, 'cliente' => $c, 'ref' => $ref] = $this->escenario(10);

        // Movimiento de entrada inicial (simula el ingreso)
        $ref->movimientos()->create([
            'tipo' => 'entrada', 'cantidad' => 10, 'saldo_resultante' => 10, 'usuario_id' => $d->id,
        ]);

        $this->actingAs($d)->post(route('salida.store'), $this->payload($c->id, $ref->id, 3));

        $ref->refresh();
        $entradas = $ref->movimientos()->where('tipo', 'entrada')->sum('cantidad');
        $salidas = $ref->movimientos()->where('tipo', 'salida')->sum('cantidad');
        $this->assertSame((int) $ref->cantidad_actual, (int) ($entradas - $salidas));
    }
}
