<?php

namespace Tests\Feature;

use App\Models\Contenedor;
use App\Models\Ingreso;
use App\Models\Referencia;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Anti-duplicados en el alta de ingresos.
 *
 * Misma barrera que Salida usa desde la feature 008: un token por intento con
 * índice UNIQUE. Un doble envío del mismo formulario aterriza en el ingreso ya
 * creado en vez de generar otro.
 */
class IngresoIdempotenciaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        Storage::fake('public');
    }

    private function operaciones(): User
    {
        $user = User::factory()->create();
        $user->assignRole('operaciones');

        return $user;
    }

    /**
     * Los archivos se regeneran en cada envío: un UploadedFile ya consumido no se
     * puede reenviar, igual que el navegador vuelve a subirlos.
     *
     * @return array<string, mixed>
     */
    private function payload(int $clienteId, string $token): array
    {
        return [
            'idempotency_key' => $token,
            'bl' => 'BL-IDEM-001',
            'cliente_id' => $clienteId,
            'fecha_ingreso' => today()->toDateString(),
            'contenedores' => [
                ['numero' => 'IDEM1111111', 'tipo_mercancia' => 'Vidrio', 'referencias' => [
                    ['codigo' => 'REF-IDEM-1', 'descripcion' => 'd', 'unidad_medida' => 'caja', 'peso' => 5, 'cantidad' => 7, 'ubicacion_patio_id' => null],
                ]],
            ],
            'documento_bl' => UploadedFile::fake()->create('bl.pdf', 20, 'application/pdf'),
            'documento_dim' => UploadedFile::fake()->create('dim.pdf', 20, 'application/pdf'),
            'documento_lista_empaque' => UploadedFile::fake()->create('lista.pdf', 20, 'application/pdf'),
        ];
    }

    public function test_el_mismo_intento_enviado_dos_veces_crea_un_solo_ingreso(): void
    {
        $usuario = $this->operaciones();
        $cliente = User::factory()->create();
        $token = (string) Str::uuid();

        $this->actingAs($usuario)->post(route('ingreso.store'), $this->payload($cliente->id, $token));
        $this->actingAs($usuario)->post(route('ingreso.store'), $this->payload($cliente->id, $token));

        $this->assertSame(1, Ingreso::count());
        $this->assertSame(1, Contenedor::count());
        $this->assertSame(1, Referencia::count());
    }

    public function test_el_reenvio_lleva_al_ingreso_ya_creado_sin_mostrar_error(): void
    {
        $usuario = $this->operaciones();
        $cliente = User::factory()->create();
        $token = (string) Str::uuid();

        $this->actingAs($usuario)->post(route('ingreso.store'), $this->payload($cliente->id, $token));
        $ingreso = Ingreso::first();

        $this->actingAs($usuario)
            ->post(route('ingreso.store'), $this->payload($cliente->id, $token))
            ->assertRedirect(route('ingreso.show', $ingreso->id))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('info');
    }

    public function test_dos_ingresos_legitimamente_distintos_si_se_crean(): void
    {
        $usuario = $this->operaciones();
        $cliente = User::factory()->create();

        // Mismo BL y mismos datos, pero intentos distintos: es válido (por
        // ejemplo, una llegada parcial que se registra en dos tandas).
        $this->actingAs($usuario)->post(route('ingreso.store'), $this->payload($cliente->id, (string) Str::uuid()));
        $this->actingAs($usuario)->post(route('ingreso.store'), $this->payload($cliente->id, (string) Str::uuid()));

        $this->assertSame(2, Ingreso::count());
    }

    public function test_el_formulario_de_alta_incluye_el_token(): void
    {
        $usuario = $this->operaciones();

        $this->actingAs($usuario)->get(route('ingreso.create'))
            ->assertOk()
            ->assertSee('name="idempotency_key"', false);
    }

    public function test_sin_token_la_peticion_se_rechaza(): void
    {
        $usuario = $this->operaciones();
        $cliente = User::factory()->create();

        $payload = $this->payload($cliente->id, (string) Str::uuid());
        unset($payload['idempotency_key']);

        $this->actingAs($usuario)
            ->post(route('ingreso.store'), $payload)
            ->assertSessionHasErrors('idempotency_key');

        $this->assertSame(0, Ingreso::count());
    }
}
