<?php

namespace Tests\Feature;

use App\Enums\CitaEstado;
use App\Models\Cita;
use App\Models\Ingreso;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Cierre del ciclo: desde un ingreso se ve el estado de cita de sus contenedores
 * y la evidencia de portería del que ya llegó.
 */
class IngresoCitasTest extends TestCase
{
    use RefreshDatabase;

    private Ingreso $ingreso;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $cliente = User::factory()->create();

        $this->ingreso = Ingreso::create([
            'bl' => 'BL-CICLO-001',
            'cliente_id' => $cliente->id,
            'fecha_ingreso' => today()->subDays(4),
            'usuario_id' => $cliente->id,
        ]);

        foreach (['AGENU1111111', 'SINCU2222222'] as $numero) {
            $this->ingreso->contenedores()->create([
                'numero' => $numero,
                'tipo_mercancia' => 'General',
                'bl' => $this->ingreso->bl,
                'estado' => 'en_patio',
                'fecha_ingreso' => today()->subDays(4),
            ]);
        }
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole('administrador');

        return $admin;
    }

    private function agendar(string $numeroContenedor): Cita
    {
        $contenedor = $this->ingreso->contenedores()->where('numero', $numeroContenedor)->firstOrFail();

        return Cita::create([
            'ingreso_id' => $this->ingreso->id,
            'contenedor_id' => $contenedor->id,
            'numero_contenedor' => $numeroContenedor,
            'tipo' => 'dry',
            'tamano' => '40',
            'condicion' => 'full',
            'fecha_esperada' => today(),
            'estado' => CitaEstado::Programada,
            'conductor_nombre' => 'Juan Pérez',
            'conductor_cedula' => '12345678',
            'placa' => 'XYZ789',
            'placa_original' => 'XYZ-789',
            'empresa' => 'Transportes Sur',
            'creado_por' => $this->ingreso->cliente_id,
        ]);
    }

    public function test_el_ingreso_distingue_contenedor_agendado_y_sin_cita(): void
    {
        $this->agendar('AGENU1111111');

        $this->actingAs($this->admin())
            ->get(route('ingreso.show', $this->ingreso))
            ->assertOk()
            ->assertSee('Cita Programada')
            ->assertSee('Sin cita agendada');
    }

    public function test_el_ingreso_muestra_la_llegada_real_y_sus_evidencias(): void
    {
        Storage::fake('public');

        $cita = $this->agendar('AGENU1111111');
        $portero = User::factory()->create();
        $portero->assignRole('portero');

        $this->actingAs($portero)->post(route('porteria.llegada', $cita), [
            'foto_vehiculo' => UploadedFile::fake()->image('v.jpg'),
            'foto_contenedor' => UploadedFile::fake()->image('c.jpg'),
            'foto_sello' => UploadedFile::fake()->image('s.jpg'),
            'foto_tiquete' => UploadedFile::fake()->image('t.jpg'),
        ]);

        $respuesta = $this->actingAs($this->admin())->get(route('ingreso.show', $this->ingreso));

        $respuesta->assertOk()
            ->assertSee('Cita Atendida')
            ->assertSee('Llegó el')
            ->assertSee($cita->registroPorteria->llegada_at->format('d/m/Y H:i'));

        $this->assertCount(4, $cita->fresh()->registroPorteria->photos);
    }

    public function test_la_relacion_de_citas_cuelga_del_ingreso(): void
    {
        $this->agendar('AGENU1111111');

        $this->assertCount(1, $this->ingreso->fresh()->citas);
    }
}
