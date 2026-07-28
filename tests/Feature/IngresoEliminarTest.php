<?php

namespace Tests\Feature;

use App\Models\Contenedor;
use App\Models\Ingreso;
use App\Models\MovimientoInventario;
use App\Models\Referencia;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Eliminación de ingresos.
 *
 * La regla: solo se borra un ingreso cuya mercancía siga intacta. En cuanto algo
 * se despachó, transfirió o vació, borrarlo dejaría registros huérfanos y
 * descuadraría el inventario.
 */
class IngresoEliminarTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        Storage::fake('public');
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole('administrador');

        return $admin;
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(int $clienteId, string $bl = 'BL-DEL-001'): array
    {
        return [
            'idempotency_key' => (string) Str::uuid(),
            'bl' => $bl,
            'cliente_id' => $clienteId,
            'fecha_ingreso' => today()->subDay()->toDateString(),
            'contenedores' => [
                ['numero' => 'DELU1111111', 'tipo_mercancia' => 'Vidrio', 'referencias' => [
                    ['codigo' => 'REF-DEL-1', 'descripcion' => 'd', 'unidad_medida' => 'caja', 'peso' => 5, 'cantidad' => 10, 'ubicacion_patio_id' => null],
                ]],
            ],
            'documento_bl' => UploadedFile::fake()->create('bl.pdf', 20, 'application/pdf'),
            'documento_dim' => UploadedFile::fake()->create('dim.pdf', 20, 'application/pdf'),
            'documento_lista_empaque' => UploadedFile::fake()->create('lista.pdf', 20, 'application/pdf'),
        ];
    }

    private function crearIngreso(User $admin, string $bl = 'BL-DEL-001'): Ingreso
    {
        $cliente = User::factory()->create();
        $this->actingAs($admin)->post(route('ingreso.store'), $this->payload($cliente->id, $bl));

        return Ingreso::where('bl', $bl)->firstOrFail();
    }

    public function test_un_ingreso_intacto_se_elimina_con_todo_lo_que_cuelga(): void
    {
        $admin = $this->admin();
        $ingreso = $this->crearIngreso($admin);

        $this->assertSame(1, Contenedor::count());
        $this->assertSame(1, Referencia::count());
        $this->assertSame(1, MovimientoInventario::count());

        $this->actingAs($admin)
            ->delete(route('ingreso.destroy', $ingreso))
            ->assertRedirect(route('ingreso.index'))
            ->assertSessionHas('success');

        $this->assertSame(0, Ingreso::count());
        $this->assertSame(0, Contenedor::count());
        $this->assertSame(0, Referencia::count());
        $this->assertSame(0, MovimientoInventario::count());
    }

    public function test_al_eliminar_queda_constancia_en_la_auditoria(): void
    {
        $admin = $this->admin();
        $ingreso = $this->crearIngreso($admin);
        $ingresoId = $ingreso->id;

        $this->actingAs($admin)->delete(route('ingreso.destroy', $ingreso));

        $this->assertDatabaseHas('cambios_auditoria', [
            'auditable_type' => Ingreso::class,
            'auditable_id' => $ingresoId,
            'usuario_id' => $admin->id,
        ]);
    }

    public function test_no_se_elimina_si_la_cantidad_de_una_referencia_ya_cambio(): void
    {
        $admin = $this->admin();
        $ingreso = $this->crearIngreso($admin);

        // Simula un despacho parcial o una novedad de vaciado.
        Referencia::first()->update(['cantidad_actual' => 4]);

        $this->actingAs($admin)
            ->delete(route('ingreso.destroy', $ingreso))
            ->assertRedirect(route('ingreso.show', $ingreso))
            ->assertSessionHas('error');

        $this->assertSame(1, Ingreso::count());
        $this->assertSame(1, Referencia::count());
    }

    public function test_no_se_elimina_si_la_mercancia_ya_fue_despachada(): void
    {
        $admin = $this->admin();
        $ingreso = $this->crearIngreso($admin);
        $referencia = Referencia::first();

        // Una orden de cargue con su tarja y su detalle = la mercancía salió.
        $ordenCargueId = DB::table('ordenes_cargue')->insertGetId([
            'cliente_id' => $ingreso->cliente_id,
            'despachador_id' => $admin->id,
            'fecha_despacho' => today(),
            'estado' => 'pendiente',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $tarjaId = DB::table('tarjas')->insertGetId([
            'orden_cargue_id' => $ordenCargueId,
            'despachador_id' => $admin->id,
            'fecha_entrega' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('tarja_detalles')->insert([
            'tarja_id' => $tarjaId,
            'referencia_id' => $referencia->id,
            'cantidad_entregada' => 3,
            'ubicacion_origen_id' => null, // nullable desde la migración 2026_06_25_000008
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin)
            ->delete(route('ingreso.destroy', $ingreso))
            ->assertSessionHas('error');

        $this->assertSame(1, Ingreso::count());
    }

    public function test_el_detalle_explica_por_que_no_se_puede_eliminar(): void
    {
        $admin = $this->admin();
        $ingreso = $this->crearIngreso($admin);
        Referencia::first()->update(['cantidad_actual' => 4]);

        $this->actingAs($admin)->get(route('ingreso.show', $ingreso))
            ->assertOk()
            ->assertSee('Este ingreso no se puede eliminar')
            ->assertSee('cuya cantidad ya cambió', false);
    }

    public function test_operaciones_tambien_puede_eliminar(): void
    {
        // Es el rol que opera el módulo y quien detecta los duplicados.
        $ingreso = $this->crearIngreso($this->admin());

        $operaciones = User::factory()->create();
        $operaciones->assignRole('operaciones');

        $this->actingAs($operaciones)
            ->delete(route('ingreso.destroy', $ingreso))
            ->assertRedirect(route('ingreso.index'))
            ->assertSessionHas('success');

        $this->assertSame(0, Ingreso::count());
    }

    public function test_a_operaciones_le_aplican_los_mismos_bloqueos(): void
    {
        $ingreso = $this->crearIngreso($this->admin());
        Referencia::first()->update(['cantidad_actual' => 4]);

        $operaciones = User::factory()->create();
        $operaciones->assignRole('operaciones');

        $this->actingAs($operaciones)
            ->delete(route('ingreso.destroy', $ingreso))
            ->assertSessionHas('error');

        $this->assertSame(1, Ingreso::count());
    }

    public function test_un_rol_sin_el_permiso_no_puede_eliminar(): void
    {
        $ingreso = $this->crearIngreso($this->admin());

        $supervisor = User::factory()->create();
        $supervisor->assignRole('supervisor');

        $this->actingAs($supervisor)
            ->delete(route('ingreso.destroy', $ingreso))
            ->assertForbidden();

        $this->assertSame(1, Ingreso::count());
    }

    public function test_el_listado_solo_muestra_el_boton_a_quien_tiene_el_permiso(): void
    {
        $this->crearIngreso($this->admin());

        $operaciones = User::factory()->create();
        $operaciones->assignRole('operaciones');

        $citas = User::factory()->create();
        $citas->assignRole('citas');

        $this->actingAs($operaciones)->get(route('ingreso.index'))
            ->assertOk()
            ->assertSee('bi-trash');

        // El rol citas consulta ingresos para agendar, pero no los borra.
        $this->actingAs($citas)->get(route('ingreso.index'))
            ->assertOk()
            ->assertDontSee('bi-trash');
    }
}
