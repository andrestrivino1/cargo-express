<?php

namespace Tests\Unit\Services;

use App\Enums\MovimientoTipo;
use App\Models\Ingreso;
use App\Models\Referencia;
use App\Models\User;
use App\Services\MovimientoInventarioService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature 010 — el ledger aprende a registrar ajustes de cantidad y bajas.
 *
 * Contrato verificado: contracts/ledger.md §2. La dirección del movimiento va en
 * el tipo (la columna `cantidad` es unsigned y nunca guarda negativos), y el
 * llamador no elige el tipo: lo deduce el servicio del signo del delta.
 */
class MovimientoInventarioAjusteTest extends TestCase
{
    use RefreshDatabase;

    private function servicio(): MovimientoInventarioService
    {
        return app(MovimientoInventarioService::class);
    }

    private function referencia(int $inicial = 10, int $actual = 10): Referencia
    {
        $cliente = User::factory()->create();

        $ingreso = Ingreso::create([
            'bl' => 'MEDU7777777',
            'bl_por_confirmar' => false,
            'cliente_id' => $cliente->id,
            'fecha_ingreso' => now()->subDay()->toDateString(),
        ]);

        $contenedor = $ingreso->contenedores()->create([
            'numero' => 'CONT-LEDGER',
            'bl' => $ingreso->bl,
            'estado' => \App\Enums\ContenedorEstado::EnPatio,
            'fecha_ingreso' => $ingreso->fecha_ingreso,
        ]);

        return $contenedor->referencias()->create([
            'cliente_id' => $cliente->id,
            'codigo' => 'REF-LEDGER',
            'descripcion' => 'Mercancía de prueba',
            'cantidad_inicial' => $inicial,
            'cantidad_actual' => $actual,
            'unidad_medida' => 'CAJA',
            'fecha_ingreso' => $ingreso->fecha_ingreso,
        ]);
    }

    public function test_delta_positivo_registra_un_ajuste_positivo(): void
    {
        $referencia = $this->referencia(inicial: 8, actual: 8);
        $usuario = User::factory()->create();

        $movimiento = $this->servicio()->registrarAjuste($referencia, 3, $usuario);

        $this->assertSame(MovimientoTipo::AjustePositivo, $movimiento->tipo);
        $this->assertSame(3, $movimiento->cantidad);
        $this->assertSame(8, $movimiento->saldo_resultante);
        $this->assertSame($usuario->id, $movimiento->usuario_id);
    }

    public function test_delta_negativo_registra_un_ajuste_negativo_con_cantidad_en_magnitud(): void
    {
        $referencia = $this->referencia(inicial: 8, actual: 4);
        $usuario = User::factory()->create();

        $movimiento = $this->servicio()->registrarAjuste($referencia, -2, $usuario);

        $this->assertSame(MovimientoTipo::AjusteNegativo, $movimiento->tipo);
        $this->assertSame(2, $movimiento->cantidad, 'La cantidad se guarda en magnitud: la columna es unsigned.');
        $this->assertSame(4, $movimiento->saldo_resultante);
    }

    public function test_delta_cero_no_registra_movimiento(): void
    {
        $referencia = $this->referencia();
        $usuario = User::factory()->create();

        $this->assertNull($this->servicio()->registrarAjuste($referencia, 0, $usuario));
        $this->assertSame(0, $referencia->movimientos()->count());
    }

    public function test_el_ajuste_guarda_documentable_y_observaciones(): void
    {
        $referencia = $this->referencia(inicial: 8, actual: 8);
        $ingreso = $referencia->contenedor->ingreso;
        $usuario = User::factory()->create();

        $movimiento = $this->servicio()->registrarAjuste(
            $referencia,
            3,
            $usuario,
            $ingreso,
            'Corrección de cantidad declarada: 5 → 8'
        );

        $this->assertSame($ingreso->getMorphClass(), $movimiento->documentable_type);
        $this->assertSame($ingreso->getKey(), $movimiento->documentable_id);
        $this->assertSame('Corrección de cantidad declarada: 5 → 8', $movimiento->observaciones);
    }

    public function test_la_baja_deja_saldo_resultante_en_cero(): void
    {
        $referencia = $this->referencia(inicial: 10, actual: 0);
        $usuario = User::factory()->create();

        $movimiento = $this->servicio()->registrarBaja($referencia, 7, $usuario, 'Cargada por duplicado');

        $this->assertSame(MovimientoTipo::Baja, $movimiento->tipo);
        $this->assertSame(7, $movimiento->cantidad);
        $this->assertSame(0, $movimiento->saldo_resultante);
        $this->assertSame('Cargada por duplicado', $movimiento->observaciones);
        $this->assertNull($movimiento->documentable_type, 'La baja no nace de un documento operativo.');
    }

    public function test_los_tipos_nuevos_declaran_su_direccion(): void
    {
        $this->assertTrue(MovimientoTipo::AjustePositivo->suma());
        $this->assertFalse(MovimientoTipo::AjusteNegativo->suma());
        $this->assertFalse(MovimientoTipo::Baja->suma());
    }
}
