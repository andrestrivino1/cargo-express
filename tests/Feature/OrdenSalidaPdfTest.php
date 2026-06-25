<?php

namespace Tests\Feature;

use App\Enums\ContenedorEstado;
use App\Models\Contenedor;
use App\Models\OrdenCargue;
use App\Models\Referencia;
use App\Models\Tarja;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrdenSalidaPdfTest extends TestCase
{
    use RefreshDatabase;

    public function test_orden_salida_pdf_se_genera_con_los_bloques_requeridos(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $despachador = User::factory()->create();
        $despachador->assignRole('despachador');

        $cliente = User::factory()->create(['name' => 'CRISTALES DE COLOMBIA', 'nit' => '9017949782']);
        $contenedor = Contenedor::create(['numero' => 'MEDU5858891', 'estado' => ContenedorEstado::EnPatio, 'fecha_ingreso' => now()]);
        $ref = Referencia::create([
            'contenedor_id' => $contenedor->id, 'cliente_id' => $cliente->id, 'codigo' => 'bronce 4mm',
            'descripcion' => 'bronce 4mm', 'cantidad_inicial' => 1, 'cantidad_actual' => 1,
            'unidad_medida' => 'unidades', 'fecha_ingreso' => now(),
        ]);
        $orden = OrdenCargue::create(['cliente_id' => $cliente->id, 'despachador_id' => $despachador->id, 'fecha_despacho' => now(), 'estado' => 'completada']);
        $tarja = $orden->tarjas()->create([
            'despachador_id' => $despachador->id, 'fecha_entrega' => now(), 'vehiculo' => 'NQL-738',
            'conductor' => 'WILMER ARANGO', 'conductor_cedula' => '123', 'transportador' => 'EL TRIUNFO',
            'destino' => 'CALI', 'consecutivo_odc' => 570,
        ]);
        $tarja->detalles()->create(['referencia_id' => $ref->id, 'cantidad_entregada' => 1, 'ubicacion_origen_id' => null]);

        $response = $this->actingAs($despachador)->get(route('salida.orden-salida.pdf', $tarja));

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('content-type'));
    }

    public function test_vista_odc_contiene_los_datos_clave(): void
    {
        $cliente = User::factory()->make(['name' => 'CRISTALES DE COLOMBIA', 'nit' => '9017949782']);
        $tarja = new Tarja([
            'consecutivo_odc' => 570, 'conductor' => 'WILMER ARANGO', 'conductor_cedula' => '123',
            'vehiculo' => 'NQL-738', 'transportador' => 'EL TRIUNFO', 'destino' => 'CALI',
        ]);
        $tarja->fecha_entrega = now();

        $html = view('pdf.orden-salida', [
            'tarja' => $tarja,
            'cliente' => $cliente,
            'detalles' => collect([['contenedor' => 'MEDU5858891', 'descripcion' => 'bronce 4mm', 'observaciones' => null, 'cantidad' => 4]]),
            'total' => 4,
            'empresa' => config('empresa'),
            'fotoMercancia' => null,
            'fotoConductor' => null,
        ])->render();

        $this->assertStringContainsString('ORDEN DE SALIDA', $html);
        $this->assertStringContainsString('ODC-570', $html);
        $this->assertStringContainsString('CRISTALES DE COLOMBIA', $html);
        $this->assertStringContainsString('9017949782', $html);
        $this->assertStringContainsString('WILMER ARANGO', $html);
        $this->assertStringContainsString('NQL-738', $html);
        $this->assertStringContainsString('EL TRIUNFO', $html);
        $this->assertStringContainsString('Firma conductor', $html);
        $this->assertStringContainsString('Firma empresa', $html);
    }
}
