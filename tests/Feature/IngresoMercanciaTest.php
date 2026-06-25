<?php

namespace Tests\Feature;

use App\Models\MovimientoInventario;
use App\Models\Photo;
use App\Models\Referencia;
use App\Models\UbicacionPatio;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class IngresoMercanciaTest extends TestCase
{
    use RefreshDatabase;

    private function operador(): User
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('operador');

        return $user;
    }

    private function payload(int $clienteId, int $ubicacionId): array
    {
        return [
            'bl' => 'BL-12345',
            'numero_contenedor' => 'MEDU5858891',
            'cliente_id' => $clienteId,
            'tipo_mercancia' => 'Vidrio',
            'referencias' => [
                [
                    'codigo' => 'bronce 4mm',
                    'descripcion' => 'Bronce 4mm',
                    'unidad_medida' => 'unidades',
                    'peso' => 120.5,
                    'cantidad' => 10,
                    'ubicacion_patio_id' => $ubicacionId,
                ],
            ],
        ];
    }

    public function test_ingreso_valido_crea_inventario_documentos_y_movimiento(): void
    {
        Storage::fake('public');
        $operador = $this->operador();
        $cliente = User::factory()->create();
        $ubicacion = UbicacionPatio::create(['modulo' => 'A', 'posicion' => '01', 'activa' => true]);

        $data = $this->payload($cliente->id, $ubicacion->id) + [
            'documento_bl' => UploadedFile::fake()->create('bl.pdf', 100, 'application/pdf'),
            'documento_dim' => UploadedFile::fake()->create('dim.pdf', 100, 'application/pdf'),
            'documento_lista_empaque' => UploadedFile::fake()->create('lista.pdf', 100, 'application/pdf'),
        ];

        $response = $this->actingAs($operador)->post(route('ingreso.store'), $data);

        $response->assertRedirect();
        $ref = Referencia::where('codigo', 'bronce 4mm')->first();
        $this->assertNotNull($ref);
        $this->assertSame(10, $ref->cantidad_actual);
        $this->assertSame('120.50', (string) $ref->peso);
        $this->assertSame(1, MovimientoInventario::where('tipo', 'entrada')->count());
        $this->assertSame(3, Photo::whereIn('categoria', ['bl', 'dim', 'lista_empaque'])->count());
    }

    public function test_ingreso_sin_campos_obligatorios_falla(): void
    {
        $operador = $this->operador();

        $response = $this->actingAs($operador)->post(route('ingreso.store'), []);

        $response->assertSessionHasErrors(['bl', 'numero_contenedor', 'cliente_id', 'tipo_mercancia', 'referencias']);
    }
}
