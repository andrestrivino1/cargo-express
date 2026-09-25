<?php

namespace Tests\Feature;

use App\Enums\MovimientoTipo;
use App\Models\CambioAuditoria;
use App\Models\Ingreso;
use App\Models\Referencia;
use App\Models\UbicacionPatio;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature 010 / US2 — retirar una referencia del inventario vigente conservando
 * su historial.
 *
 * El retiro NO es un borrado: la fila permanece (soft delete) para que los
 * movimientos, órdenes de salida, transferencias y vaciados que la mencionan
 * sigan teniendo respaldo. Lo que se pierde es la visibilidad en el inventario
 * vigente y la posibilidad de usarla en operaciones nuevas.
 */
class InventarioRetiroTest extends TestCase
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

    private function ingresoCon(User $cliente, int $declarada = 10, int $disponible = 10, string $sufijo = '1'): Ingreso
    {
        $ingreso = Ingreso::create([
            'bl' => "MEDU200001{$sufijo}",
            'bl_por_confirmar' => false,
            'cliente_id' => $cliente->id,
            'fecha_ingreso' => now()->subDays(4)->toDateString(),
        ]);

        $contenedor = $ingreso->contenedores()->create([
            'numero' => "CONT-{$sufijo}",
            'bl' => $ingreso->bl,
            'estado' => \App\Enums\ContenedorEstado::EnPatio,
            'fecha_ingreso' => $ingreso->fecha_ingreso,
        ]);

        $referencia = $contenedor->referencias()->create([
            'cliente_id' => $cliente->id,
            'codigo' => "REF-RET-{$sufijo}",
            'descripcion' => 'Mercancía retirable',
            'cantidad_inicial' => $declarada,
            'cantidad_actual' => $disponible,
            'unidad_medida' => 'CAJA',
            'fecha_ingreso' => $ingreso->fecha_ingreso,
        ]);

        $referencia->movimientos()->create([
            'tipo' => MovimientoTipo::Entrada,
            'cantidad' => $declarada,
            'saldo_resultante' => $declarada,
            'usuario_id' => $cliente->id,
            'documentable_type' => $ingreso->getMorphClass(),
            'documentable_id' => $ingreso->getKey(),
        ]);

        if ($disponible < $declarada) {
            $referencia->movimientos()->create([
                'tipo' => MovimientoTipo::Salida,
                'cantidad' => $declarada - $disponible,
                'saldo_resultante' => $disponible,
                'usuario_id' => $cliente->id,
            ]);
        }

        return $ingreso;
    }

    private function referenciaDe(Ingreso $ingreso): Referencia
    {
        return $ingreso->contenedores->first()->referencias->first();
    }

    // ----- Escenario 1: el retiro saca la referencia del listado -----

    public function test_retirar_saca_la_referencia_del_inventario_y_deja_constancia(): void
    {
        $admin = $this->admin();
        $referencia = $this->referenciaDe($this->ingresoCon($this->cliente()));

        $response = $this->actingAs($admin)->delete(route('inventario.retirar', $referencia));

        $response->assertRedirect(route('inventario.index'));
        $response->assertSessionHas('success');

        $this->assertNull(Referencia::find($referencia->id), 'Sale del inventario vigente.');

        $retirada = Referencia::withTrashed()->find($referencia->id);
        $this->assertNotNull($retirada, 'La fila permanece: el retiro no borra historial.');
        $this->assertNotNull($retirada->deleted_at);
        $this->assertSame($admin->id, $retirada->retirado_por, 'La constancia del retiro es quién y cuándo.');

        // El flash de éxito nombra la referencia y sobrevive una petición más:
        // se limpia la sesión para que el listado se mire en limpio.
        $this->flushSession();
        $this->actingAs($admin)->get(route('inventario.index'))
            ->assertDontSee($referencia->codigo);
    }

    // ----- Escenario 2: exportables y vista del cliente -----

    public function test_la_referencia_retirada_no_aparece_en_exportables_ni_para_el_cliente(): void
    {
        $admin = $this->admin();
        $cliente = $this->cliente();
        $referencia = $this->referenciaDe($this->ingresoCon($cliente));

        $this->actingAs($admin)->delete(route('inventario.retirar', $referencia));
        $this->flushSession(); // el flash de éxito nombra la referencia

        $this->actingAs($cliente)->get(route('inventario.index'))
            ->assertDontSee($referencia->codigo);

        // Los exportables se verifican sobre la consulta que los alimenta: el PDF
        // sale como binario de DomPDF y el Excel como archivo, así que buscar el
        // código dentro del entregable no probaría nada.
        $servicio = app(\App\Services\InventarioService::class);

        $codigosExcel = $servicio->exportarInventario([], $admin)->query()->pluck('codigo');
        $this->assertNotContains($referencia->codigo, $codigosExcel);

        $this->assertSame(0, \App\Models\Referencia::where('codigo', $referencia->codigo)->count(),
            'Ninguna consulta de inventario vigente puede devolverla.');
    }

    // ----- Escenario 3 y L8: el historial sobrevive -----

    public function test_retirar_una_referencia_con_movimientos_conserva_su_historial(): void
    {
        $admin = $this->admin();
        $referencia = $this->referenciaDe($this->ingresoCon($this->cliente(), declarada: 10, disponible: 6));

        $movimientosAntes = $referencia->movimientos()->count();

        $this->actingAs($admin)->delete(route('inventario.retirar', $referencia));

        $salida = \App\Models\MovimientoInventario::where('referencia_id', $referencia->id)
            ->where('tipo', MovimientoTipo::Salida)
            ->first();

        $this->assertNotNull($salida);
        $this->assertNotNull($salida->referencia, 'La relación histórica debe seguir resolviendo (withTrashed).');
        $this->assertSame($referencia->codigo, $salida->referencia->codigo);
        $this->assertGreaterThan($movimientosAntes, \App\Models\MovimientoInventario::where('referencia_id', $referencia->id)->count());
    }

    // ----- Escenario 4: no seleccionable en operaciones nuevas -----

    public function test_la_referencia_retirada_no_es_seleccionable_en_operaciones_nuevas(): void
    {
        $admin = $this->admin();
        $cliente = $this->cliente();
        $referencia = $this->referenciaDe($this->ingresoCon($cliente));

        $this->actingAs($admin)->delete(route('inventario.retirar', $referencia));

        // Salida: el listado AJAX de referencias del cliente no la ofrece.
        $json = $this->actingAs($admin)->getJson(route('salida.referencias-cliente', $cliente));
        $json->assertOk();
        $this->assertNotContains($referencia->codigo, array_column($json->json(), 'codigo'));

        // Ubicación en patio: tampoco aparece como pendiente de ubicar.
        $this->actingAs($admin)->get(route('inventario.ubicar'))
            ->assertDontSee($referencia->codigo);
    }

    public function test_asignar_ubicacion_a_una_referencia_retirada_falla_la_validacion(): void
    {
        $admin = $this->admin();
        $referencia = $this->referenciaDe($this->ingresoCon($this->cliente()));
        $ubicacion = UbicacionPatio::create([
            'modulo' => 'A',
            'posicion' => '01',
            'activa' => true,
        ]);

        $this->actingAs($admin)->delete(route('inventario.retirar', $referencia));

        $this->actingAs($admin)->post(route('inventario.asignar-ubicacion'), [
            'referencia_id' => $referencia->id,
            'ubicacion_patio_id' => $ubicacion->id,
        ])->assertSessionHasErrors('referencia_id');
    }

    // ----- Escenario 5 y L6: la baja cuadra el ledger -----

    public function test_el_retiro_registra_la_baja_del_disponible_y_deja_la_cantidad_en_cero(): void
    {
        $admin = $this->admin();
        $referencia = $this->referenciaDe($this->ingresoCon($this->cliente(), declarada: 10, disponible: 7));

        $this->actingAs($admin)->delete(route('inventario.retirar', $referencia));

        $baja = \App\Models\MovimientoInventario::where('referencia_id', $referencia->id)
            ->where('tipo', MovimientoTipo::Baja)
            ->first();

        $this->assertNotNull($baja);
        $this->assertSame(7, $baja->cantidad);
        $this->assertSame(0, $baja->saldo_resultante);
        $this->assertSame('Retiro del inventario', $baja->observaciones);
        $this->assertSame(0, Referencia::withTrashed()->find($referencia->id)->cantidad_actual);
    }

    // ----- L7: sin disponible no hay movimiento de baja -----

    public function test_retirar_una_referencia_sin_disponible_no_registra_baja(): void
    {
        $admin = $this->admin();
        $referencia = $this->referenciaDe($this->ingresoCon($this->cliente(), declarada: 10, disponible: 0));

        $this->actingAs($admin)->delete(route('inventario.retirar', $referencia));

        $this->assertSame(0, \App\Models\MovimientoInventario::where('referencia_id', $referencia->id)
            ->where('tipo', MovimientoTipo::Baja)
            ->count());
        $this->assertNotNull(Referencia::withTrashed()->find($referencia->id)->deleted_at);
    }

    // ----- Auditoría -----

    public function test_el_retiro_queda_registrado_en_la_auditoria(): void
    {
        $admin = $this->admin();
        $referencia = $this->referenciaDe($this->ingresoCon($this->cliente()));

        $this->actingAs($admin)->delete(route('inventario.retirar', $referencia));

        $auditoria = CambioAuditoria::where('auditable_type', $referencia->getMorphClass())
            ->where('auditable_id', $referencia->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($auditoria);
        $this->assertSame($admin->id, $auditoria->usuario_id);
        $this->assertArrayHasKey('retirado_por', $auditoria->cambios);
    }

    // ----- Filtro de retiradas -----

    public function test_el_filtro_incluir_retiradas_las_muestra_solo_a_quien_puede_retirar(): void
    {
        $admin = $this->admin();
        $referencia = $this->referenciaDe($this->ingresoCon($this->cliente()));

        $this->actingAs($admin)->delete(route('inventario.retirar', $referencia));

        $this->actingAs($admin)->get(route('inventario.index', ['incluir_retiradas' => 1]))
            ->assertSee($referencia->codigo);
    }

    public function test_el_cliente_no_ve_retiradas_aunque_fuerce_el_parametro(): void
    {
        $admin = $this->admin();
        $cliente = $this->cliente();
        $referencia = $this->referenciaDe($this->ingresoCon($cliente));

        $this->actingAs($admin)->delete(route('inventario.retirar', $referencia));
        $this->flushSession(); // el flash de éxito nombra la referencia

        $this->actingAs($cliente)->get(route('inventario.index', ['incluir_retiradas' => 1]))
            ->assertDontSee($referencia->codigo);
    }

    // ----- Autorización -----

    public function test_un_rol_sin_el_permiso_no_puede_retirar(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $supervisor = User::factory()->create();
        $supervisor->assignRole('supervisor');

        $referencia = $this->referenciaDe($this->ingresoCon($this->cliente()));

        $this->actingAs($supervisor)->delete(route('inventario.retirar', $referencia))
            ->assertForbidden();

        $this->assertNotNull(Referencia::find($referencia->id));
    }

    public function test_el_cliente_no_puede_retirar_su_propia_mercancia(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $cliente = $this->cliente();
        $referencia = $this->referenciaDe($this->ingresoCon($cliente));

        $this->actingAs($cliente)->delete(route('inventario.retirar', $referencia))
            ->assertForbidden();

        $this->assertNotNull(Referencia::find($referencia->id));
    }

    // ----- L10: regresión del borrado físico -----

    public function test_eliminar_un_ingreso_borra_las_referencias_de_verdad(): void
    {
        $admin = $this->admin();
        $ingreso = $this->ingresoCon($this->cliente());
        $referencia = $this->referenciaDe($ingreso);

        $this->actingAs($admin)->delete(route('ingreso.destroy', $ingreso))
            ->assertRedirect(route('ingreso.index'));

        $this->assertNull(
            Referencia::withTrashed()->find($referencia->id),
            'Eliminar un ingreso es borrado físico, no retiro: no puede quedar rastro resucitable.'
        );
    }
}
