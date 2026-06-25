<?php

namespace Tests\Feature;

use App\Models\Contenedor;
use App\Models\Ingreso;
use App\Models\MovimientoInventario;
use App\Models\Referencia;
use App\Models\UbicacionPatio;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class IngresoMultiContenedorTest extends TestCase
{
    use RefreshDatabase;

    private function operador(): User
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('operador');

        return $user;
    }

    private function docs(): array
    {
        return [
            'documento_bl' => UploadedFile::fake()->create('bl.pdf', 50, 'application/pdf'),
            'documento_dim' => UploadedFile::fake()->create('dim.pdf', 50, 'application/pdf'),
            'documento_lista_empaque' => UploadedFile::fake()->create('lista.pdf', 50, 'application/pdf'),
        ];
    }

    public function test_ingreso_un_bl_dos_contenedores_con_referencias(): void
    {
        Storage::fake('public');
        $operador = $this->operador();
        $cliente = User::factory()->create();
        $ub = UbicacionPatio::create(['modulo' => 'A', 'posicion' => '01', 'activa' => true]);

        $data = [
            'bl' => 'BL-555',
            'cliente_id' => $cliente->id,
            'fecha_ingreso' => now()->toDateString(),
            'contenedores' => [
                ['numero' => 'MEDU1', 'tipo_mercancia' => 'Vidrio', 'referencias' => [
                    ['codigo' => 'bronce 4mm', 'descripcion' => 'Bronce', 'unidad_medida' => 'unidades', 'peso' => 10, 'cantidad' => 5, 'ubicacion_patio_id' => $ub->id],
                    ['codigo' => 'claro 5mm', 'descripcion' => 'Claro', 'unidad_medida' => 'unidades', 'peso' => 20, 'cantidad' => 3, 'ubicacion_patio_id' => null], // sin ubicar
                ]],
                ['numero' => 'PRSU2', 'tipo_mercancia' => 'Vidrio', 'referencias' => [
                    ['codigo' => 'bronce 4mm', 'descripcion' => 'Bronce', 'unidad_medida' => 'unidades', 'peso' => 10, 'cantidad' => 7, 'ubicacion_patio_id' => $ub->id], // mismo código que C1
                ]],
            ],
        ] + $this->docs();

        $response = $this->actingAs($operador)->post(route('ingreso.store'), $data);

        $response->assertRedirect();
        $this->assertSame(1, Ingreso::count());
        $ingreso = Ingreso::first();
        $this->assertSame('BL-555', $ingreso->bl);
        $this->assertSame(2, Contenedor::where('ingreso_id', $ingreso->id)->count());
        $this->assertSame(3, Referencia::count());

        // código repetido entre contenedores: cantidades separadas
        $cantidades = Referencia::where('codigo', 'bronce 4mm')->pluck('cantidad_actual')->sort()->values()->all();
        $this->assertSame([5, 7], $cantidades);

        // referencia sin ubicación
        $this->assertNull(Referencia::where('codigo', 'claro 5mm')->first()->ubicacion_patio_id);

        // movimientos de entrada y documentos en el Ingreso
        $this->assertSame(3, MovimientoInventario::where('tipo', 'entrada')->count());
        $this->assertSame(3, $ingreso->documentos()->count());
    }

    public function test_contenedor_sin_referencias_es_rechazado(): void
    {
        $operador = $this->operador();
        $cliente = User::factory()->create();

        $data = [
            'bl' => 'BL-1', 'cliente_id' => $cliente->id, 'fecha_ingreso' => now()->toDateString(),
            'contenedores' => [
                ['numero' => 'C1', 'tipo_mercancia' => 'Vidrio'], // sin referencias
            ],
        ] + $this->docs();

        $response = $this->actingAs($operador)->post(route('ingreso.store'), $data);

        $response->assertSessionHasErrors('contenedores.0.referencias');
    }

    public function test_numeros_de_contenedor_duplicados_son_rechazados(): void
    {
        $operador = $this->operador();
        $cliente = User::factory()->create();
        $ref = ['codigo' => 'r', 'descripcion' => 'd', 'unidad_medida' => 'u', 'peso' => 1, 'cantidad' => 1, 'ubicacion_patio_id' => null];

        $data = [
            'bl' => 'BL-1', 'cliente_id' => $cliente->id, 'fecha_ingreso' => now()->toDateString(),
            'contenedores' => [
                ['numero' => 'DUP', 'tipo_mercancia' => 'Vidrio', 'referencias' => [$ref]],
                ['numero' => 'DUP', 'tipo_mercancia' => 'Vidrio', 'referencias' => [$ref]],
            ],
        ] + $this->docs();

        $response = $this->actingAs($operador)->post(route('ingreso.store'), $data);

        $response->assertSessionHasErrors('contenedores');
    }
}
