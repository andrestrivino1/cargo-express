<?php

namespace Tests\Feature;

use App\Enums\ContenedorEstado;
use App\Enums\OrdenCargueEstado;
use App\Models\Contenedor;
use App\Models\OrdenCargue;
use App\Models\Referencia;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SecuenciaOdcSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SalidaNitEdicionTest extends TestCase
{
    use RefreshDatabase;

    private function base(): array
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(SecuenciaOdcSeeder::class);
        $despachador = User::factory()->create();
        $despachador->assignRole('despachador');
        $cliente = User::factory()->create(['nit' => null]);
        $contenedor = Contenedor::create(['numero' => 'C1', 'estado' => ContenedorEstado::EnPatio, 'fecha_ingreso' => now()]);
        $ref = Referencia::create([
            'contenedor_id' => $contenedor->id, 'cliente_id' => $cliente->id,
            'codigo' => 'r1', 'cantidad_inicial' => 10, 'cantidad_actual' => 10,
            'unidad_medida' => 'unidades', 'fecha_ingreso' => now(),
        ]);

        return compact('despachador', 'cliente', 'ref');
    }

    public function test_nit_capturado_en_la_salida_se_guarda_en_el_cliente(): void
    {
        Storage::fake('public');
        ['despachador' => $d, 'cliente' => $c, 'ref' => $ref] = $this->base();

        $this->actingAs($d)->post(route('salida.store'), [
            'cliente_id' => $c->id,
            'nit' => '9017949782',
            'fecha_salida' => now()->format('Y-m-d'),
            'conductor' => 'Wilmer', 'placa_vehiculo' => 'NQL-738',
            'transportador' => 'El Triunfo', 'destino' => 'Cali',
            'detalles' => [['referencia_id' => $ref->id, 'cantidad' => 2]],
            'foto_mercancia' => UploadedFile::fake()->image('m.jpg'),
            'foto_conductor' => UploadedFile::fake()->image('co.jpg'),
        ])->assertRedirect();

        $this->assertSame('9017949782', $c->fresh()->nit);
    }

    public function test_editar_salida_actualiza_cabecera_y_nit(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('administrador');
        $cliente = User::factory()->create(['nit' => null]);
        $orden = OrdenCargue::create([
            'cliente_id' => $cliente->id, 'despachador_id' => $admin->id,
            'fecha_despacho' => now(), 'estado' => OrdenCargueEstado::Completada,
        ]);
        $tarja = $orden->tarjas()->create([
            'despachador_id' => $admin->id, 'fecha_entrega' => now(),
            'conductor' => 'Viejo', 'vehiculo' => 'AAA-111', 'transportador' => 'X', 'destino' => 'Y',
            'consecutivo_odc' => 999,
        ]);

        $this->actingAs($admin)->put(route('salida.update', $tarja), [
            'nit' => '900123',
            'fecha_salida' => now()->format('Y-m-d'),
            'conductor' => 'Nuevo Conductor', 'conductor_cedula' => '555',
            'placa_vehiculo' => 'BBB-222', 'transportador' => 'JC', 'destino' => 'Cartago',
            'observaciones' => 'editado',
        ])->assertRedirect(route('salida.show', $tarja));

        $tarja->refresh();
        $this->assertSame('Nuevo Conductor', $tarja->conductor);
        $this->assertSame('BBB-222', $tarja->vehiculo);
        $this->assertSame('Cartago', $tarja->destino);
        $this->assertSame('900123', $cliente->fresh()->nit);
    }
}
