<?php

namespace Tests\Feature;

use App\Models\Contenedor;
use App\Models\Ingreso;
use App\Models\MovimientoInventario;
use App\Models\Photo;
use App\Models\Referencia;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Feature 007 — Editar ingreso con referencias e imágenes del BL.
 */
class IngresoEditarTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('administrador');

        return $admin;
    }

    private function ingresoConReferencias(User $cliente, int $contenedores = 1, int $referenciasPorContenedor = 2): Ingreso
    {
        $ingreso = Ingreso::create([
            'bl' => 'MEDU1234567',
            'bl_por_confirmar' => true,
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

            for ($r = 1; $r <= $referenciasPorContenedor; $r++) {
                $contenedor->referencias()->create([
                    'cliente_id' => $cliente->id,
                    'codigo' => "REF-{$c}-{$r}",
                    'descripcion' => "Mercancía {$c}-{$r}",
                    'cantidad_inicial' => 10,
                    'cantidad_actual' => 10,
                    'unidad_medida' => 'CAJA',
                    'fecha_ingreso' => $ingreso->fecha_ingreso,
                ]);
            }
        }

        return $ingreso;
    }

    // ----- User Story 1: ver referencias + confirmar BL -----

    public function test_editar_muestra_todas_las_referencias_del_bl_en_varios_contenedores(): void
    {
        $admin = $this->admin();
        $cliente = User::factory()->create();
        $ingreso = $this->ingresoConReferencias($cliente, contenedores: 2, referenciasPorContenedor: 2);

        $response = $this->actingAs($admin)->get(route('ingreso.editar', $ingreso));

        $response->assertOk();
        // las 4 referencias (2 contenedores x 2) deben mostrarse
        $response->assertSee('REF-1-1');
        $response->assertSee('REF-1-2');
        $response->assertSee('REF-2-1');
        $response->assertSee('REF-2-2');
        $response->assertSee('CONT-1');
        $response->assertSee('CONT-2');
    }

    public function test_editar_confirma_bl_y_conserva_referencias(): void
    {
        $admin = $this->admin();
        $cliente = User::factory()->create();
        $ingreso = $this->ingresoConReferencias($cliente, contenedores: 1, referenciasPorContenedor: 2);

        $response = $this->actingAs($admin)->put(route('ingreso.update', $ingreso), [
            'bl' => 'BL-REAL-007',
            'cliente_id' => $cliente->id,
            'fecha_ingreso' => $ingreso->fecha_ingreso->toDateString(),
        ]);

        $response->assertRedirect(route('ingreso.show', $ingreso));
        $ingreso->refresh();
        $this->assertSame('BL-REAL-007', $ingreso->bl);
        $this->assertFalse($ingreso->bl_por_confirmar);
        $this->assertSame(2, Referencia::whereIn('contenedor_id', $ingreso->contenedores->pluck('id'))->count());
    }

    public function test_editar_ingreso_sin_referencias_muestra_mensaje_y_permite_guardar(): void
    {
        $admin = $this->admin();
        $cliente = User::factory()->create();
        $ingreso = Ingreso::create([
            'bl' => 'PEND-0001',
            'bl_por_confirmar' => true,
            'cliente_id' => $cliente->id,
            'fecha_ingreso' => now()->toDateString(),
        ]);

        $get = $this->actingAs($admin)->get(route('ingreso.editar', $ingreso));
        $get->assertOk();
        $get->assertSee('Sin referencias');

        $put = $this->actingAs($admin)->put(route('ingreso.update', $ingreso), [
            'bl' => 'BL-REAL-EMPTY',
            'cliente_id' => $cliente->id,
            'fecha_ingreso' => now()->toDateString(),
        ]);
        $put->assertRedirect(route('ingreso.show', $ingreso));
        $this->assertFalse($ingreso->fresh()->bl_por_confirmar);
    }

    // ----- User Story 2: imágenes -----

    public function test_editar_sube_imagenes_y_las_asocia_al_ingreso(): void
    {
        Storage::fake('public');
        $admin = $this->admin();
        $cliente = User::factory()->create();
        $ingreso = $this->ingresoConReferencias($cliente);

        $response = $this->actingAs($admin)->put(route('ingreso.update', $ingreso), [
            'bl' => 'BL-IMG-001',
            'cliente_id' => $cliente->id,
            'fecha_ingreso' => $ingreso->fecha_ingreso->toDateString(),
            'fotos' => [
                UploadedFile::fake()->image('bl1.jpg'),
                UploadedFile::fake()->image('bl2.png'),
            ],
        ]);

        $response->assertRedirect(route('ingreso.show', $ingreso));
        $fotos = $ingreso->fotos()->get();
        $this->assertCount(2, $fotos);
        $this->assertTrue($fotos->every(fn (Photo $f) => $f->tipo === 'foto'));
        Storage::disk('public')->assertExists($fotos->first()->ruta);
    }

    public function test_subir_imagenes_no_borra_las_existentes(): void
    {
        Storage::fake('public');
        $admin = $this->admin();
        $cliente = User::factory()->create();
        $ingreso = $this->ingresoConReferencias($cliente);
        $ingreso->guardarFotos([UploadedFile::fake()->image('previa.jpg')], "ingresos/{$ingreso->id}");

        $this->actingAs($admin)->put(route('ingreso.update', $ingreso), [
            'bl' => 'BL-IMG-002',
            'cliente_id' => $cliente->id,
            'fecha_ingreso' => $ingreso->fecha_ingreso->toDateString(),
            'fotos' => [UploadedFile::fake()->image('nueva.jpg')],
        ]);

        $this->assertCount(2, $ingreso->fotos()->get());
    }

    public function test_archivo_no_imagen_es_rechazado_sin_perder_datos(): void
    {
        Storage::fake('public');
        $admin = $this->admin();
        $cliente = User::factory()->create();
        $ingreso = $this->ingresoConReferencias($cliente);

        $response = $this->actingAs($admin)->put(route('ingreso.update', $ingreso), [
            'bl' => 'BL-INVALIDO',
            'cliente_id' => $cliente->id,
            'fecha_ingreso' => $ingreso->fecha_ingreso->toDateString(),
            'fotos' => [UploadedFile::fake()->create('documento.pdf', 100, 'application/pdf')],
        ]);

        $response->assertSessionHasErrors('fotos.0');
        $ingreso->refresh();
        // no se confirmó ni se crearon fotos
        $this->assertSame('MEDU1234567', $ingreso->bl);
        $this->assertTrue($ingreso->bl_por_confirmar);
        $this->assertCount(0, $ingreso->fotos()->get());
    }

    // ----- User Story 3: agregar referencia -----

    public function test_agregar_referencia_crea_referencia_y_movimiento_de_entrada(): void
    {
        $admin = $this->admin();
        $cliente = User::factory()->create();
        $ingreso = $this->ingresoConReferencias($cliente, contenedores: 1, referenciasPorContenedor: 1);
        $contenedor = $ingreso->contenedores->first();

        $response = $this->actingAs($admin)->put(route('ingreso.update', $ingreso), [
            'bl' => 'BL-REF-001',
            'cliente_id' => $cliente->id,
            'fecha_ingreso' => $ingreso->fecha_ingreso->toDateString(),
            'nueva_referencia' => [
                'contenedor_id' => $contenedor->id,
                'codigo' => 'NUEVA-001',
                'descripcion' => 'Referencia agregada en edición',
                'unidad_medida' => 'PALLET',
                'cantidad' => 5,
            ],
        ]);

        $response->assertRedirect(route('ingreso.show', $ingreso));
        $referencia = Referencia::where('codigo', 'NUEVA-001')->first();
        $this->assertNotNull($referencia);
        $this->assertSame($contenedor->id, $referencia->contenedor_id);
        $this->assertSame(5, (int) $referencia->cantidad_actual);
        $this->assertSame($cliente->id, $referencia->cliente_id);
        $this->assertSame(1, MovimientoInventario::where('referencia_id', $referencia->id)->count());
    }

    public function test_referencia_incompleta_es_rechazada(): void
    {
        $admin = $this->admin();
        $cliente = User::factory()->create();
        $ingreso = $this->ingresoConReferencias($cliente, contenedores: 1, referenciasPorContenedor: 1);
        $contenedor = $ingreso->contenedores->first();

        $response = $this->actingAs($admin)->put(route('ingreso.update', $ingreso), [
            'bl' => 'BL-REF-002',
            'cliente_id' => $cliente->id,
            'fecha_ingreso' => $ingreso->fecha_ingreso->toDateString(),
            'nueva_referencia' => [
                'contenedor_id' => $contenedor->id,
                'codigo' => 'INCOMPLETA',
                // falta descripcion, unidad_medida y cantidad
            ],
        ]);

        $response->assertSessionHasErrors([
            'nueva_referencia.descripcion',
            'nueva_referencia.unidad_medida',
            'nueva_referencia.cantidad',
        ]);
        $this->assertNull(Referencia::where('codigo', 'INCOMPLETA')->first());
    }

    public function test_contenedor_ajeno_al_ingreso_es_rechazado(): void
    {
        $admin = $this->admin();
        $cliente = User::factory()->create();
        $ingreso = $this->ingresoConReferencias($cliente, contenedores: 1, referenciasPorContenedor: 1);

        // contenedor de OTRO ingreso
        $otro = $this->ingresoConReferencias($cliente, contenedores: 1, referenciasPorContenedor: 1);
        $contenedorAjeno = $otro->contenedores->first();

        $response = $this->actingAs($admin)->put(route('ingreso.update', $ingreso), [
            'bl' => 'BL-REF-003',
            'cliente_id' => $cliente->id,
            'fecha_ingreso' => $ingreso->fecha_ingreso->toDateString(),
            'nueva_referencia' => [
                'contenedor_id' => $contenedorAjeno->id,
                'codigo' => 'AJENA',
                'descripcion' => 'No debe crearse',
                'unidad_medida' => 'CAJA',
                'cantidad' => 1,
            ],
        ]);

        $response->assertSessionHasErrors('nueva_referencia.contenedor_id');
        $this->assertNull(Referencia::where('codigo', 'AJENA')->first());
    }

    public function test_usuario_sin_rol_no_puede_editar(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $cliente = User::factory()->create();
        $ingreso = $this->ingresoConReferencias($cliente);

        $sinRol = User::factory()->create();

        $this->actingAs($sinRol)->get(route('ingreso.editar', $ingreso))->assertForbidden();
    }
}
