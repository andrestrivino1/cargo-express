<?php

namespace Tests\Unit;

use App\Models\Ingreso;
use App\Models\MovimientoInventario;
use App\Models\User;
use App\Services\IngresoMercanciaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Feature 007 — pruebas del servicio para la edición de ingresos
 * (carga de fotos aditiva + creación de referencia con movimiento de inventario).
 */
class IngresoMercanciaServiceTest extends TestCase
{
    use RefreshDatabase;

    private function service(): IngresoMercanciaService
    {
        return app(IngresoMercanciaService::class);
    }

    private function ingresoConContenedor(User $cliente): Ingreso
    {
        $ingreso = Ingreso::create([
            'bl' => 'MEDU0000001',
            'bl_por_confirmar' => true,
            'cliente_id' => $cliente->id,
            'fecha_ingreso' => now()->subDays(2)->toDateString(),
        ]);
        $ingreso->contenedores()->create([
            'numero' => 'CONT-X',
            'bl' => $ingreso->bl,
            'estado' => \App\Enums\ContenedorEstado::EnPatio,
            'fecha_ingreso' => $ingreso->fecha_ingreso,
        ]);

        return $ingreso;
    }

    public function test_actualizar_guarda_fotos_de_forma_aditiva(): void
    {
        Storage::fake('public');
        $usuario = User::factory()->create();
        $cliente = User::factory()->create();
        $ingreso = $this->ingresoConContenedor($cliente);
        $ingreso->guardarFotos([UploadedFile::fake()->image('previa.jpg')], "ingresos/{$ingreso->id}");

        $this->service()->actualizar(
            $ingreso,
            ['bl' => 'BL-1', 'cliente_id' => $cliente->id, 'fecha_ingreso' => $ingreso->fecha_ingreso->toDateString()],
            [UploadedFile::fake()->image('nueva.jpg')],
            null,
            $usuario,
        );

        $fotos = $ingreso->fotos()->get();
        $this->assertCount(2, $fotos);
        Storage::disk('public')->assertExists($fotos->last()->ruta);
    }

    public function test_actualizar_crea_referencia_con_campos_heredados_y_movimiento(): void
    {
        $usuario = User::factory()->create();
        $cliente = User::factory()->create();
        $ingreso = $this->ingresoConContenedor($cliente);
        $contenedor = $ingreso->contenedores->first();

        $this->service()->actualizar(
            $ingreso,
            ['bl' => 'BL-2', 'cliente_id' => $cliente->id, 'fecha_ingreso' => $ingreso->fecha_ingreso->toDateString()],
            [],
            [
                'contenedor_id' => $contenedor->id,
                'codigo' => 'SRV-001',
                'descripcion' => 'Creada por servicio',
                'unidad_medida' => 'CAJA',
                'cantidad' => 7,
            ],
            $usuario,
        );

        $referencia = $contenedor->referencias()->where('codigo', 'SRV-001')->first();
        $this->assertNotNull($referencia);
        $this->assertSame($cliente->id, $referencia->cliente_id);
        $this->assertSame(7, (int) $referencia->cantidad_inicial);
        $this->assertSame(7, (int) $referencia->cantidad_actual);
        $this->assertSame(1, MovimientoInventario::where('referencia_id', $referencia->id)->count());
    }
}
