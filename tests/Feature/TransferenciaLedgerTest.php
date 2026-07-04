<?php

namespace Tests\Feature;

use App\Enums\ContenedorEstado;
use App\Models\Contenedor;
use App\Models\MovimientoInventario;
use App\Models\Referencia;
use App\Models\UbicacionPatio;
use App\Models\User;
use App\Services\TransferenciaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verifica que las transferencias mantengan el ledger `movimientos_inventario`
 * cuadrado: una salida en la referencia origen y una entrada en la destino,
 * ambas ligadas a la transferencia.
 */
class TransferenciaLedgerTest extends TestCase
{
    use RefreshDatabase;

    private function referencia(User $cliente, ?UbicacionPatio $ubicacion, int $saldo): Referencia
    {
        $contenedor = Contenedor::create([
            'numero' => 'MEDU'.mt_rand(1000000, 9999999),
            'estado' => ContenedorEstado::EnPatio,
            'fecha_ingreso' => now(),
        ]);

        return Referencia::create([
            'contenedor_id' => $contenedor->id,
            'cliente_id' => $cliente->id,
            'codigo' => 'bronce 4mm',
            'descripcion' => 'Bronce 4mm',
            'cantidad_inicial' => $saldo,
            'cantidad_actual' => $saldo,
            'unidad_medida' => 'unidades',
            'ubicacion_patio_id' => $ubicacion?->id,
            'fecha_ingreso' => now(),
        ]);
    }

    public function test_transferencia_entre_modulos_registra_salida_y_entrada_en_el_ledger(): void
    {
        $usuario = User::factory()->create();
        $cliente = User::factory()->create();
        $origen = UbicacionPatio::create(['modulo' => 'A', 'posicion' => '01']);
        $destino = UbicacionPatio::create(['modulo' => 'B', 'posicion' => '02']);
        $ref = $this->referencia($cliente, $origen, 10);

        app(TransferenciaService::class)->transferirEntreModulos([
            'referencia_id' => $ref->id,
            'ubicacion_destino_id' => $destino->id,
            'cantidad' => 4,
        ], $usuario);

        $ref->refresh();
        $this->assertSame(6, (int) $ref->cantidad_actual);

        $refDestino = Referencia::where('ubicacion_patio_id', $destino->id)->firstOrFail();
        $this->assertSame(4, (int) $refDestino->cantidad_actual);

        // Salida en origen: saldo cuadra con el ledger
        $salida = MovimientoInventario::where('referencia_id', $ref->id)->where('tipo', 'salida')->firstOrFail();
        $this->assertSame(4, (int) $salida->cantidad);
        $this->assertSame(6, (int) $salida->saldo_resultante);

        // Entrada en destino
        $entrada = MovimientoInventario::where('referencia_id', $refDestino->id)->where('tipo', 'entrada')->firstOrFail();
        $this->assertSame(4, (int) $entrada->cantidad);
        $this->assertSame(4, (int) $entrada->saldo_resultante);
    }

    public function test_transferencia_entre_clientes_registra_salida_y_entrada_en_el_ledger(): void
    {
        $usuario = User::factory()->create();
        $clienteOrigen = User::factory()->create();
        $clienteDestino = User::factory()->create();
        $origen = UbicacionPatio::create(['modulo' => 'C', 'posicion' => '03']);
        $destino = UbicacionPatio::create(['modulo' => 'D', 'posicion' => '04']);
        $ref = $this->referencia($clienteOrigen, $origen, 8);

        app(TransferenciaService::class)->transferirEntreClientes([
            'referencia_id' => $ref->id,
            'cliente_destino_id' => $clienteDestino->id,
            'ubicacion_destino_id' => $destino->id,
            'cantidad' => 3,
            'motivo' => 'Venta entre clientes',
            'autorizacion_cliente' => 'AUT-001',
        ], $usuario);

        $ref->refresh();
        $this->assertSame(5, (int) $ref->cantidad_actual);

        $refDestino = Referencia::where('cliente_id', $clienteDestino->id)->firstOrFail();
        $this->assertSame(3, (int) $refDestino->cantidad_actual);

        $salida = MovimientoInventario::where('referencia_id', $ref->id)->where('tipo', 'salida')->firstOrFail();
        $this->assertSame(3, (int) $salida->cantidad);
        $this->assertSame(5, (int) $salida->saldo_resultante);

        $entrada = MovimientoInventario::where('referencia_id', $refDestino->id)->where('tipo', 'entrada')->firstOrFail();
        $this->assertSame(3, (int) $entrada->cantidad);
        $this->assertSame(3, (int) $entrada->saldo_resultante);
    }
}
