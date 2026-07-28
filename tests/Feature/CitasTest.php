<?php

namespace Tests\Feature;

use App\Enums\CitaEstado;
use App\Models\Cita;
use App\Models\Contenedor;
use App\Models\Ingreso;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CitasTest extends TestCase
{
    use RefreshDatabase;

    private function usuarioCitas(): User
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('citas');

        return $user;
    }

    private function ingresoConContenedor(string $numero = 'ABCU1234567'): Contenedor
    {
        $cliente = User::factory()->create();

        $ingreso = Ingreso::create([
            'bl' => 'BL-TEST-001',
            'cliente_id' => $cliente->id,
            'fecha_ingreso' => today()->subDays(2),
            'usuario_id' => $cliente->id,
        ]);

        return $ingreso->contenedores()->create([
            'numero' => $numero,
            'tipo_mercancia' => 'Vidrio',
            'bl' => $ingreso->bl,
            'estado' => 'en_patio',
            'fecha_ingreso' => today()->subDays(2),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Contenedor $contenedor, array $sobreescribir = []): array
    {
        return array_merge([
            'ingreso_id' => $contenedor->ingreso_id,
            'contenedor_id' => $contenedor->id,
            'tipo' => 'dry',
            'tamano' => '40',
            'condicion' => 'full',
            'fecha_esperada' => today()->toDateString(),
            'conductor_nombre' => 'Juan Pérez',
            'conductor_cedula' => '1.234.567-8',
            'placa' => 'abc-123',
            'empresa' => 'Transportes del Norte',
        ], $sobreescribir);
    }

    public function test_agendar_cita_completa_queda_programada(): void
    {
        $usuario = $this->usuarioCitas();
        $contenedor = $this->ingresoConContenedor();

        $this->actingAs($usuario)
            ->post(route('citas.store'), $this->payload($contenedor))
            ->assertRedirect();

        $cita = Cita::first();

        $this->assertNotNull($cita);
        $this->assertSame(CitaEstado::Programada, $cita->estado);
        $this->assertSame($contenedor->id, $cita->contenedor_id);
        $this->assertSame($usuario->id, $cita->creado_por);
    }

    public function test_la_placa_y_la_cedula_se_guardan_normalizadas_y_originales(): void
    {
        $usuario = $this->usuarioCitas();
        $contenedor = $this->ingresoConContenedor();

        $this->actingAs($usuario)->post(route('citas.store'), $this->payload($contenedor));

        $cita = Cita::first();

        $this->assertSame('ABC123', $cita->placa);
        $this->assertSame('abc-123', $cita->placa_original);
        $this->assertSame('12345678', $cita->conductor_cedula);
        $this->assertSame('1.234.567-8', $cita->conductor_cedula_original);
    }

    public function test_faltar_un_campo_obligatorio_lo_señala_y_no_guarda(): void
    {
        $usuario = $this->usuarioCitas();
        $contenedor = $this->ingresoConContenedor();

        $this->actingAs($usuario)
            ->post(route('citas.store'), $this->payload($contenedor, ['placa' => '']))
            ->assertSessionHasErrors('placa');

        $this->assertSame(0, Cita::count());
    }

    public function test_contenedor_de_otro_ingreso_es_rechazado(): void
    {
        $usuario = $this->usuarioCitas();
        $contenedorA = $this->ingresoConContenedor('AAAU1111111');
        $contenedorB = $this->ingresoConContenedor('BBBU2222222');

        $this->actingAs($usuario)
            ->post(route('citas.store'), $this->payload($contenedorA, ['contenedor_id' => $contenedorB->id]))
            ->assertSessionHasErrors('contenedor_id');

        $this->assertSame(0, Cita::count());
    }

    public function test_editar_una_cita_registra_quien_la_modifico(): void
    {
        $usuario = $this->usuarioCitas();
        $otro = User::factory()->create();
        $otro->assignRole('citas');
        $contenedor = $this->ingresoConContenedor();

        $this->actingAs($usuario)->post(route('citas.store'), $this->payload($contenedor));
        $cita = Cita::first();

        $this->actingAs($otro)
            ->put(route('citas.update', $cita), $this->payload($contenedor, ['conductor_nombre' => 'Pedro Gómez']))
            ->assertRedirect();

        $cita->refresh();

        $this->assertSame('Pedro Gómez', $cita->conductor_nombre);
        $this->assertSame($otro->id, $cita->actualizado_por);
    }

    public function test_contenedor_ya_agendado_advierte_sin_bloquear(): void
    {
        $usuario = $this->usuarioCitas();
        $contenedor = $this->ingresoConContenedor();

        $this->actingAs($usuario)->post(route('citas.store'), $this->payload($contenedor));

        $this->actingAs($usuario)
            ->post(route('citas.store'), $this->payload($contenedor))
            ->assertSessionHas('warning');

        // Se advierte, pero la segunda cita SÍ se crea: un contenedor puede volver.
        $this->assertSame(2, Cita::count());
    }

    public function test_cita_atendida_no_se_puede_editar(): void
    {
        $usuario = $this->usuarioCitas();
        $contenedor = $this->ingresoConContenedor();

        $this->actingAs($usuario)->post(route('citas.store'), $this->payload($contenedor));
        $cita = Cita::first();
        $cita->update(['estado' => CitaEstado::Atendida]);

        $this->actingAs($usuario)->get(route('citas.editar', $cita))->assertForbidden();
        $this->actingAs($usuario)
            ->put(route('citas.update', $cita), $this->payload($contenedor))
            ->assertForbidden();
    }

    public function test_cancelar_una_cita_la_deja_cancelada(): void
    {
        $usuario = $this->usuarioCitas();
        $contenedor = $this->ingresoConContenedor();

        $this->actingAs($usuario)->post(route('citas.store'), $this->payload($contenedor));
        $cita = Cita::first();

        $this->actingAs($usuario)->post(route('citas.cancelar', $cita))->assertRedirect();

        $this->assertSame(CitaEstado::Cancelada, $cita->fresh()->estado);
    }

    public function test_cita_con_fecha_pasada_se_muestra_vencida_sin_tarea_programada(): void
    {
        $usuario = $this->usuarioCitas();
        $contenedor = $this->ingresoConContenedor();

        $this->actingAs($usuario)->post(route('citas.store'), $this->payload($contenedor, [
            'fecha_esperada' => today()->subDays(3)->toDateString(),
        ]));

        $cita = Cita::first();

        // La columna sigue diciendo "programada"; el estado efectivo, no.
        $this->assertSame(CitaEstado::Programada, $cita->estado);
        $this->assertSame(CitaEstado::Vencida, $cita->estadoEfectivo());
    }

    public function test_se_admite_agendamiento_retroactivo(): void
    {
        $usuario = $this->usuarioCitas();
        $contenedor = $this->ingresoConContenedor();

        $this->actingAs($usuario)
            ->post(route('citas.store'), $this->payload($contenedor, [
                'fecha_esperada' => today()->subDays(5)->toDateString(),
            ]))
            ->assertSessionHasNoErrors();

        $this->assertSame(1, Cita::count());
    }

    public function test_listado_filtra_por_placa_sin_importar_el_formato(): void
    {
        $usuario = $this->usuarioCitas();
        $contenedor = $this->ingresoConContenedor();

        $this->actingAs($usuario)->post(route('citas.store'), $this->payload($contenedor));

        $this->actingAs($usuario)
            ->get(route('citas.index', ['placa' => 'ABC 123']))
            ->assertOk()
            ->assertSee('ABCU1234567');
    }

    public function test_rol_citas_no_accede_a_salida_ni_vaciado(): void
    {
        $usuario = $this->usuarioCitas();

        $this->actingAs($usuario)->get(route('salida.index'))->assertForbidden();
        $this->actingAs($usuario)->get(route('vaciado.index'))->assertForbidden();
        $this->actingAs($usuario)->get(route('inventario.index'))->assertForbidden();
    }

    public function test_rol_citas_consulta_ingresos_pero_no_los_crea(): void
    {
        $usuario = $this->usuarioCitas();

        $this->actingAs($usuario)->get(route('ingreso.index'))->assertOk();
        $this->actingAs($usuario)->get(route('ingreso.create'))->assertForbidden();
    }
}
