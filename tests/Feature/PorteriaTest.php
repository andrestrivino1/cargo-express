<?php

namespace Tests\Feature;

use App\Enums\CitaEstado;
use App\Models\Cita;
use App\Models\Contenedor;
use App\Models\Ingreso;
use App\Models\PorteriaNovedad;
use App\Models\PorteriaRegistro;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PorteriaTest extends TestCase
{
    use RefreshDatabase;

    private function portero(): User
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('portero');

        return $user;
    }

    private function crearCita(string $fecha, string $numero = 'ABCU1234567', string $placa = 'ABC123'): Cita
    {
        $cliente = User::factory()->create();

        $ingreso = Ingreso::create([
            'bl' => 'BL-'.$numero,
            'cliente_id' => $cliente->id,
            'fecha_ingreso' => today()->subDays(5),
            'usuario_id' => $cliente->id,
        ]);

        $contenedor = $ingreso->contenedores()->create([
            'numero' => $numero,
            'tipo_mercancia' => 'Vidrio',
            'bl' => $ingreso->bl,
            'estado' => 'en_patio',
            'fecha_ingreso' => today()->subDays(5),
        ]);

        return Cita::create([
            'ingreso_id' => $ingreso->id,
            'contenedor_id' => $contenedor->id,
            'numero_contenedor' => $numero,
            'tipo' => 'dry',
            'tamano' => '40',
            'condicion' => 'full',
            'fecha_esperada' => $fecha,
            'estado' => CitaEstado::Programada,
            'conductor_nombre' => 'Juan Pérez',
            'conductor_cedula' => '12345678',
            'placa' => $placa,
            'placa_original' => $placa,
            'empresa' => 'Transportes del Norte',
            'creado_por' => $cliente->id,
        ]);
    }

    /**
     * @return array<string, UploadedFile>
     */
    private function cuatroFotos(): array
    {
        return [
            'foto_vehiculo' => UploadedFile::fake()->image('vehiculo.jpg'),
            'foto_contenedor' => UploadedFile::fake()->image('contenedor.jpg'),
            'foto_sello' => UploadedFile::fake()->image('sello.jpg'),
            'foto_tiquete' => UploadedFile::fake()->image('tiquete.jpg'),
        ];
    }

    public function test_lista_las_citas_de_hoy_y_las_futuras_pero_no_las_pasadas(): void
    {
        $portero = $this->portero();
        $this->crearCita(today()->toDateString(), 'HOYU1111111');
        $this->crearCita(today()->subDay()->toDateString(), 'AYER2222222');
        $this->crearCita(today()->addDay()->toDateString(), 'MANA3333333');
        $this->crearCita(today()->addDays(9)->toDateString(), 'LEJO4444444');

        $this->actingAs($portero)->get(route('porteria.index'))
            ->assertOk()
            ->assertSee('HOYU1111111')
            // Futuras: visibles para seguimiento.
            ->assertSee('MANA3333333')
            ->assertSee('LEJO4444444')
            ->assertSee('Próximas citas')
            // Pasadas: fuera del módulo del portero.
            ->assertDontSee('AYER2222222');
    }

    public function test_una_cita_futura_se_puede_consultar_pero_no_confirmar(): void
    {
        Storage::fake('public');
        $portero = $this->portero();
        $cita = $this->crearCita(today()->addDays(3)->toDateString(), 'FUTU5555555');

        // Se consulta…
        $this->actingAs($portero)->get(route('porteria.show', $cita))
            ->assertOk()
            ->assertSee('FUTU5555555')
            ->assertSee('Todavía no se puede confirmar');

        // …pero no se confirma.
        $this->actingAs($portero)
            ->post(route('porteria.llegada', $cita), $this->cuatroFotos())
            ->assertSessionHasErrors('cita');

        $this->assertSame(CitaEstado::Programada, $cita->fresh()->estado);
        $this->assertSame(0, PorteriaRegistro::count());
    }

    public function test_busqueda_por_placa_ignora_guiones_espacios_y_mayusculas(): void
    {
        $portero = $this->portero();
        $this->crearCita(today()->toDateString(), 'ABCU1234567', 'ABC123');

        foreach (['abc 123', 'ABC-123', 'abc123'] as $termino) {
            $this->actingAs($portero)->get(route('porteria.index', ['q' => $termino]))
                ->assertOk()
                ->assertSee('ABCU1234567');
        }
    }

    public function test_confirmar_llegada_con_las_cuatro_fotos_marca_atendida(): void
    {
        Storage::fake('public');
        $portero = $this->portero();
        $cita = $this->crearCita(today()->toDateString());

        $this->actingAs($portero)
            ->post(route('porteria.llegada', $cita), $this->cuatroFotos())
            ->assertRedirect(route('porteria.show', $cita));

        $cita->refresh();
        $registro = PorteriaRegistro::first();

        $this->assertSame(CitaEstado::Atendida, $cita->estado);
        $this->assertNotNull($registro);
        $this->assertSame($portero->id, $registro->portero_id);
        $this->assertNotNull($registro->llegada_at);
        $this->assertCount(4, $registro->photos);
    }

    public function test_las_cuatro_evidencias_quedan_identificadas_por_categoria(): void
    {
        Storage::fake('public');
        $portero = $this->portero();
        $cita = $this->crearCita(today()->toDateString());

        $this->actingAs($portero)->post(route('porteria.llegada', $cita), $this->cuatroFotos());

        $categorias = PorteriaRegistro::first()->photos->pluck('categoria')->sort()->values()->all();

        $this->assertSame(['contenedor', 'sello', 'tiquete', 'vehiculo'], $categorias);
    }

    public function test_falta_una_foto_se_rechaza_nombrando_la_evidencia(): void
    {
        Storage::fake('public');
        $portero = $this->portero();
        $cita = $this->crearCita(today()->toDateString());

        $fotos = $this->cuatroFotos();
        unset($fotos['foto_sello']);

        $this->actingAs($portero)
            ->post(route('porteria.llegada', $cita), $fotos)
            ->assertSessionHasErrors('foto_sello');

        $this->assertSame(CitaEstado::Programada, $cita->fresh()->estado);
        $this->assertSame(0, PorteriaRegistro::count());
    }

    public function test_una_cita_atendida_no_se_puede_reconfirmar(): void
    {
        Storage::fake('public');
        $portero = $this->portero();
        $cita = $this->crearCita(today()->toDateString());

        $this->actingAs($portero)->post(route('porteria.llegada', $cita), $this->cuatroFotos());

        $this->actingAs($portero)
            ->post(route('porteria.llegada', $cita), $this->cuatroFotos())
            ->assertForbidden();

        $this->assertSame(1, PorteriaRegistro::count());
    }

    public function test_una_cita_pasada_no_es_alcanzable_desde_porteria(): void
    {
        $portero = $this->portero();
        $cita = $this->crearCita(today()->subDay()->toDateString());

        $this->actingAs($portero)->get(route('porteria.show', $cita))->assertNotFound();
        $this->actingAs($portero)
            ->post(route('porteria.llegada', $cita), $this->cuatroFotos())
            ->assertNotFound();
    }

    public function test_la_busqueda_tambien_encuentra_citas_futuras(): void
    {
        $portero = $this->portero();
        $this->crearCita(today()->addDays(4)->toDateString(), 'FUTU5555555', 'FUT999');

        $this->actingAs($portero)->get(route('porteria.index', ['q' => 'fut-999']))
            ->assertOk()
            ->assertSee('FUTU5555555');
    }

    public function test_sin_citas_para_hoy_muestra_mensaje_explicito(): void
    {
        $portero = $this->portero();

        $this->actingAs($portero)->get(route('porteria.index'))
            ->assertOk()
            ->assertSee('Sin citas para hoy');
    }

    public function test_vehiculo_sin_cita_muestra_mensaje_y_ofrece_novedad(): void
    {
        $portero = $this->portero();
        $this->crearCita(today()->toDateString(), 'ABCU1234567', 'ABC123');

        $this->actingAs($portero)->get(route('porteria.index', ['q' => 'ZZZ999']))
            ->assertOk()
            ->assertSee('Sin cita para hoy')
            ->assertSee('Reportar novedad');
    }

    public function test_registrar_novedad_no_crea_ninguna_cita(): void
    {
        $portero = $this->portero();

        $this->actingAs($portero)->post(route('porteria.novedad.store'), [
            'placa' => 'zzz-999',
            'descripcion' => 'Se presentó sin cita.',
        ])->assertRedirect(route('porteria.index'));

        $this->assertSame(1, PorteriaNovedad::count());
        $this->assertSame(0, Cita::count());
        $this->assertSame('ZZZ999', PorteriaNovedad::first()->placa);
    }

    public function test_novedad_exige_placa_o_contenedor(): void
    {
        $portero = $this->portero();

        $this->actingAs($portero)->post(route('porteria.novedad.store'), [
            'descripcion' => 'Sin identificar.',
        ])->assertSessionHasErrors(['placa', 'numero_contenedor']);

        $this->assertSame(0, PorteriaNovedad::count());
    }

    public function test_portero_no_accede_a_ingreso_ni_salida(): void
    {
        $portero = $this->portero();

        $this->actingAs($portero)->get(route('ingreso.index'))->assertForbidden();
        $this->actingAs($portero)->get(route('salida.index'))->assertForbidden();
        $this->actingAs($portero)->get(route('citas.index'))->assertForbidden();
        $this->actingAs($portero)->get(route('vaciado.index'))->assertForbidden();
    }
}
