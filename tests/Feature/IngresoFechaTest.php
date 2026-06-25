<?php

namespace Tests\Feature;

use App\Models\Ingreso;
use App\Models\Referencia;
use App\Models\UbicacionPatio;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class IngresoFechaTest extends TestCase
{
    use RefreshDatabase;

    private function operador(): User
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('operador');

        return $user;
    }

    private function payload(int $clienteId, string $fecha): array
    {
        return [
            'bl' => 'BL-FECHA',
            'cliente_id' => $clienteId,
            'fecha_ingreso' => $fecha,
            'contenedores' => [
                ['numero' => 'C1', 'tipo_mercancia' => 'Vidrio', 'referencias' => [
                    ['codigo' => 'r1', 'descripcion' => 'd', 'unidad_medida' => 'u', 'peso' => 1, 'cantidad' => 2, 'ubicacion_patio_id' => null],
                ]],
            ],
            'documento_bl' => UploadedFile::fake()->create('bl.pdf', 50, 'application/pdf'),
            'documento_dim' => UploadedFile::fake()->create('dim.pdf', 50, 'application/pdf'),
            'documento_lista_empaque' => UploadedFile::fake()->create('lista.pdf', 50, 'application/pdf'),
        ];
    }

    public function test_fecha_retroactiva_se_guarda_en_ingreso_y_referencia(): void
    {
        Storage::fake('public');
        $operador = $this->operador();
        $cliente = User::factory()->create();
        $fecha = now()->subDays(10)->toDateString();

        $this->actingAs($operador)->post(route('ingreso.store'), $this->payload($cliente->id, $fecha));

        $this->assertSame($fecha, Ingreso::first()->fecha_ingreso->toDateString());
        $this->assertSame($fecha, Referencia::first()->fecha_ingreso->toDateString());
    }

    public function test_fecha_futura_es_rechazada(): void
    {
        Storage::fake('public');
        $operador = $this->operador();
        $cliente = User::factory()->create();
        $futura = now()->addDays(3)->toDateString();

        $response = $this->actingAs($operador)->post(route('ingreso.store'), $this->payload($cliente->id, $futura));

        $response->assertSessionHasErrors('fecha_ingreso');
        $this->assertSame(0, Ingreso::count());
    }

    public function test_reporte_ingresos_muestra_fecha_capturada(): void
    {
        Storage::fake('public');
        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('administrador');
        $cliente = User::factory()->create();
        $fecha = now()->subDays(7)->toDateString();

        $this->actingAs($admin)->post(route('ingreso.store'), $this->payload($cliente->id, $fecha));

        $response = $this->actingAs($admin)->get(route('reportes.ingresos'));
        $response->assertOk();
        $response->assertSee(now()->subDays(7)->format('d/m/Y'));
    }
}
