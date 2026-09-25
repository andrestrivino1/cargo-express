<?php

namespace Tests\Feature;

use App\Enums\MovimientoTipo;
use App\Models\CambioAuditoria;
use App\Models\Ingreso;
use App\Models\MovimientoInventario;
use App\Models\Referencia;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature 010 / US1 — corregir la cantidad declarada de una referencia desde la
 * edición del ingreso.
 *
 * Reglas verificadas (data-model.md §3):
 *   consumido        = cantidad_inicial − cantidad_actual
 *   nueva_disponible = nueva_declarada − consumido
 *   rechazo si         nueva_declarada < consumido
 */
class IngresoCorregirCantidadTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('administrador');

        return $admin;
    }

    private function cliente(): User
    {
        $cliente = User::factory()->create();
        $cliente->assignRole('cliente');

        return $cliente;
    }

    /**
     * Crea un ingreso con N contenedores y una referencia por contenedor.
     * `$declarada` y `$disponible` permiten simular mercancía ya consumida sin
     * montar una orden de salida completa: el invariante del sistema es que todo
     * lo que sale baja `cantidad_actual`.
     */
    private function ingresoCon(User $cliente, int $declarada = 5, int $disponible = 5, int $contenedores = 1): Ingreso
    {
        $ingreso = Ingreso::create([
            'bl' => 'MEDU1000010',
            'bl_por_confirmar' => false,
            'cliente_id' => $cliente->id,
            'fecha_ingreso' => now()->subDays(3)->toDateString(),
        ]);

        for ($c = 1; $c <= $contenedores; $c++) {
            $contenedor = $ingreso->contenedores()->create([
                'numero' => "CONT-{$c}",
                'bl' => $ingreso->bl,
                'estado' => \App\Enums\ContenedorEstado::EnPatio,
                'fecha_ingreso' => $ingreso->fecha_ingreso,
            ]);

            $referencia = $contenedor->referencias()->create([
                'cliente_id' => $cliente->id,
                'codigo' => "REF-{$c}",
                'descripcion' => "Mercancía {$c}",
                'cantidad_inicial' => $declarada,
                'cantidad_actual' => $disponible,
                'unidad_medida' => 'CAJA',
                'fecha_ingreso' => $ingreso->fecha_ingreso,
            ]);

            // Entrada original en el ledger, como la escribe el ingreso real.
            $referencia->movimientos()->create([
                'tipo' => MovimientoTipo::Entrada,
                'cantidad' => $declarada,
                'saldo_resultante' => $declarada,
                'usuario_id' => $cliente->id,
                'documentable_type' => $ingreso->getMorphClass(),
                'documentable_id' => $ingreso->getKey(),
            ]);

            // Si hay consumo previo, su salida correspondiente.
            if ($disponible < $declarada) {
                $referencia->movimientos()->create([
                    'tipo' => MovimientoTipo::Salida,
                    'cantidad' => $declarada - $disponible,
                    'saldo_resultante' => $disponible,
                    'usuario_id' => $cliente->id,
                ]);
            }
        }

        return $ingreso;
    }

    /** @return array<string, mixed> */
    private function payload(Ingreso $ingreso, array $referencias = []): array
    {
        return [
            'bl' => $ingreso->bl,
            'cliente_id' => $ingreso->cliente_id,
            'fecha_ingreso' => $ingreso->fecha_ingreso->toDateString(),
            'referencias' => $referencias,
        ];
    }

    // ----- Escenario 1 y L1: corrección sin movimientos previos -----

    public function test_corregir_cantidad_sin_movimientos_ajusta_declarada_y_disponible(): void
    {
        $admin = $this->admin();
        $cliente = $this->cliente();
        $ingreso = $this->ingresoCon($cliente, declarada: 5, disponible: 5);
        $referencia = $ingreso->contenedores->first()->referencias->first();

        $response = $this->actingAs($admin)->put(
            route('ingreso.update', $ingreso),
            $this->payload($ingreso, [$referencia->id => 8])
        );

        $response->assertRedirect(route('ingreso.show', $ingreso));

        $referencia->refresh();
        $this->assertSame(8, $referencia->cantidad_inicial);
        $this->assertSame(8, $referencia->cantidad_actual);

        $ajuste = $referencia->movimientos()->where('tipo', MovimientoTipo::AjustePositivo)->first();
        $this->assertNotNull($ajuste);
        $this->assertSame(3, $ajuste->cantidad);
        $this->assertSame(8, $ajuste->saldo_resultante);
        $this->assertSame($admin->id, $ajuste->usuario_id);
    }

    // ----- Escenario 2: auditoría -----

    public function test_la_correccion_queda_auditada_con_valor_anterior_y_nuevo(): void
    {
        $admin = $this->admin();
        $cliente = $this->cliente();
        $ingreso = $this->ingresoCon($cliente, declarada: 5, disponible: 5);
        $referencia = $ingreso->contenedores->first()->referencias->first();

        $this->actingAs($admin)->put(
            route('ingreso.update', $ingreso),
            $this->payload($ingreso, [$referencia->id => 8])
        );

        $auditoria = CambioAuditoria::where('auditable_type', $referencia->getMorphClass())
            ->where('auditable_id', $referencia->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($auditoria);
        $this->assertSame($admin->id, $auditoria->usuario_id);
        $this->assertSame(5, (int) $auditoria->cambios['cantidad_inicial']['anterior']);
        $this->assertSame(8, (int) $auditoria->cambios['cantidad_inicial']['nuevo']);
    }

    // ----- Escenario 3: varias referencias en un mismo guardado -----

    public function test_corrige_dos_referencias_de_contenedores_distintos_en_un_guardado(): void
    {
        $admin = $this->admin();
        $cliente = $this->cliente();
        $ingreso = $this->ingresoCon($cliente, declarada: 5, disponible: 5, contenedores: 3);

        $referencias = Referencia::whereIn('contenedor_id', $ingreso->contenedores->pluck('id'))
            ->orderBy('id')
            ->get();

        $this->actingAs($admin)->put(
            route('ingreso.update', $ingreso),
            $this->payload($ingreso, [
                $referencias[0]->id => 9,
                $referencias[1]->id => 2,
            ])
        );

        $this->assertSame(9, $referencias[0]->fresh()->cantidad_inicial);
        $this->assertSame(2, $referencias[1]->fresh()->cantidad_inicial);
        $this->assertSame(5, $referencias[2]->fresh()->cantidad_inicial, 'La referencia no enviada no se toca.');
    }

    // ----- Escenario 4: cantidades inválidas, todo o nada -----

    public function test_cantidad_cero_rechaza_el_guardado_completo(): void
    {
        $admin = $this->admin();
        $cliente = $this->cliente();
        $ingreso = $this->ingresoCon($cliente, declarada: 5, disponible: 5, contenedores: 2);

        $referencias = Referencia::whereIn('contenedor_id', $ingreso->contenedores->pluck('id'))
            ->orderBy('id')
            ->get();

        $response = $this->actingAs($admin)->put(
            route('ingreso.update', $ingreso),
            $this->payload($ingreso, [
                $referencias[0]->id => 9,   // válida
                $referencias[1]->id => 0,   // inválida: tumba todo el guardado
            ])
        );

        $response->assertSessionHasErrors("referencias.{$referencias[1]->id}");
        $this->assertSame(5, $referencias[0]->fresh()->cantidad_inicial, 'Todo o nada: ninguna corrección se aplica.');
        $this->assertSame(5, $referencias[1]->fresh()->cantidad_inicial);
    }

    // ----- Escenario 5 y L2: corrección con mercancía ya despachada -----

    public function test_corregir_al_alza_con_mercancia_despachada_conserva_lo_consumido(): void
    {
        $admin = $this->admin();
        $cliente = $this->cliente();
        $ingreso = $this->ingresoCon($cliente, declarada: 5, disponible: 3); // 2 ya salieron
        $referencia = $ingreso->contenedores->first()->referencias->first();

        $this->actingAs($admin)->put(
            route('ingreso.update', $ingreso),
            $this->payload($ingreso, [$referencia->id => 8])
        );

        $referencia->refresh();
        $this->assertSame(8, $referencia->cantidad_inicial);
        $this->assertSame(6, $referencia->cantidad_actual, '8 declaradas − 2 consumidas = 6 disponibles.');

        $ajuste = $referencia->movimientos()->where('tipo', MovimientoTipo::AjustePositivo)->first();
        $this->assertSame(3, $ajuste->cantidad);
        $this->assertSame(6, $ajuste->saldo_resultante);
    }

    // ----- L3: corrección a la baja -----

    public function test_corregir_a_la_baja_registra_un_ajuste_negativo(): void
    {
        $admin = $this->admin();
        $cliente = $this->cliente();
        $ingreso = $this->ingresoCon($cliente, declarada: 10, disponible: 6); // 4 ya salieron
        $referencia = $ingreso->contenedores->first()->referencias->first();

        $this->actingAs($admin)->put(
            route('ingreso.update', $ingreso),
            $this->payload($ingreso, [$referencia->id => 8])
        );

        $referencia->refresh();
        $this->assertSame(8, $referencia->cantidad_inicial);
        $this->assertSame(4, $referencia->cantidad_actual);

        $ajuste = $referencia->movimientos()->where('tipo', MovimientoTipo::AjusteNegativo)->first();
        $this->assertNotNull($ajuste);
        $this->assertSame(2, $ajuste->cantidad);
        $this->assertSame(4, $ajuste->saldo_resultante);
    }

    // ----- Escenario 6 y L4: no se puede declarar menos de lo que ya salió -----

    public function test_rechaza_declarar_menos_de_lo_ya_consumido(): void
    {
        $admin = $this->admin();
        $cliente = $this->cliente();
        $ingreso = $this->ingresoCon($cliente, declarada: 5, disponible: 3); // 2 ya salieron
        $referencia = $ingreso->contenedores->first()->referencias->first();

        $response = $this->actingAs($admin)->put(
            route('ingreso.update', $ingreso),
            $this->payload($ingreso, [$referencia->id => 1])
        );

        $response->assertSessionHasErrors("referencias.{$referencia->id}");

        $referencia->refresh();
        $this->assertSame(5, $referencia->cantidad_inicial);
        $this->assertSame(3, $referencia->cantidad_actual);
        $this->assertSame(0, $referencia->movimientos()
            ->whereIn('tipo', [MovimientoTipo::AjustePositivo, MovimientoTipo::AjusteNegativo])
            ->count());
    }

    // ----- L5: guardar sin cambios no escribe nada -----

    public function test_guardar_sin_cambiar_cantidades_no_escribe_movimientos_ni_auditoria(): void
    {
        $admin = $this->admin();
        $cliente = $this->cliente();
        $ingreso = $this->ingresoCon($cliente, declarada: 5, disponible: 5);
        $referencia = $ingreso->contenedores->first()->referencias->first();

        $movimientosAntes = $referencia->movimientos()->count();

        $this->actingAs($admin)->put(
            route('ingreso.update', $ingreso),
            $this->payload($ingreso, [$referencia->id => 5])
        );

        $this->assertSame($movimientosAntes, $referencia->movimientos()->count());
        $this->assertSame(0, CambioAuditoria::where('auditable_type', $referencia->getMorphClass())
            ->where('auditable_id', $referencia->id)
            ->count());
    }

    // ----- Seguridad: no se puede tocar mercancía de otro ingreso -----

    public function test_no_permite_corregir_una_referencia_de_otro_ingreso(): void
    {
        $admin = $this->admin();
        $cliente = $this->cliente();

        $ingreso = $this->ingresoCon($cliente, declarada: 5, disponible: 5);

        $otro = Ingreso::create([
            'bl' => 'MEDU9999999',
            'bl_por_confirmar' => false,
            'cliente_id' => $cliente->id,
            'fecha_ingreso' => now()->subDays(5)->toDateString(),
        ]);
        $contenedorAjeno = $otro->contenedores()->create([
            'numero' => 'CONT-AJENO',
            'bl' => $otro->bl,
            'estado' => \App\Enums\ContenedorEstado::EnPatio,
            'fecha_ingreso' => $otro->fecha_ingreso,
        ]);
        $referenciaAjena = $contenedorAjeno->referencias()->create([
            'cliente_id' => $cliente->id,
            'codigo' => 'REF-AJENA',
            'descripcion' => 'Mercancía de otro BL',
            'cantidad_inicial' => 4,
            'cantidad_actual' => 4,
            'unidad_medida' => 'CAJA',
            'fecha_ingreso' => $otro->fecha_ingreso,
        ]);

        $response = $this->actingAs($admin)->put(
            route('ingreso.update', $ingreso),
            $this->payload($ingreso, [$referenciaAjena->id => 99])
        );

        $response->assertSessionHasErrors("referencias.{$referenciaAjena->id}");
        $this->assertSame(4, $referenciaAjena->fresh()->cantidad_inicial);
    }

    // ----- Escenario 7: autorización -----

    public function test_un_rol_sin_permiso_de_edicion_no_puede_corregir_cantidades(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $operaciones = User::factory()->create();
        $operaciones->assignRole('operaciones');

        $cliente = $this->cliente();
        $ingreso = $this->ingresoCon($cliente, declarada: 5, disponible: 5);
        $referencia = $ingreso->contenedores->first()->referencias->first();

        $response = $this->actingAs($operaciones)->put(
            route('ingreso.update', $ingreso),
            $this->payload($ingreso, [$referencia->id => 8])
        );

        $response->assertForbidden();
        $this->assertSame(5, $referencia->fresh()->cantidad_inicial);
    }

    // ----- L9: el ajuste no contamina el reporte de Ingresos -----

    public function test_el_ajuste_no_cuenta_como_entrada_en_el_reporte_de_ingresos(): void
    {
        $admin = $this->admin();
        $cliente = $this->cliente();
        $ingreso = $this->ingresoCon($cliente, declarada: 5, disponible: 5);
        $referencia = $ingreso->contenedores->first()->referencias->first();

        $entradasAntes = MovimientoInventario::where('tipo', MovimientoTipo::Entrada)->count();

        $this->actingAs($admin)->put(
            route('ingreso.update', $ingreso),
            $this->payload($ingreso, [$referencia->id => 8])
        );

        $this->assertSame(
            $entradasAntes,
            MovimientoInventario::where('tipo', MovimientoTipo::Entrada)->count(),
            'Una corrección de digitación no es mercancía que llegue.'
        );
    }
}
