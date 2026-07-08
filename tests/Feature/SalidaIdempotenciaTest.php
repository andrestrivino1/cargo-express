<?php

namespace Tests\Feature;

use App\Enums\ContenedorEstado;
use App\Models\Contenedor;
use App\Models\IdempotencyKey;
use App\Models\MovimientoInventario;
use App\Models\OrdenCargue;
use App\Models\Referencia;
use App\Models\Tarja;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SecuenciaOdcSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class SalidaIdempotenciaTest extends TestCase
{
    use RefreshDatabase;

    private function escenario(int $saldo = 10): array
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(SecuenciaOdcSeeder::class);

        $despachador = User::factory()->create();
        $despachador->assignRole('despachador');

        $cliente = User::factory()->create(['nit' => '9017949782']);
        $contenedor = Contenedor::create([
            'numero' => 'MEDU5858891',
            'estado' => ContenedorEstado::EnPatio,
            'fecha_ingreso' => now(),
        ]);
        $ref = Referencia::create([
            'contenedor_id' => $contenedor->id,
            'cliente_id' => $cliente->id,
            'codigo' => 'bronce 4mm',
            'descripcion' => 'Bronce 4mm',
            'cantidad_inicial' => $saldo,
            'cantidad_actual' => $saldo,
            'unidad_medida' => 'unidades',
            'fecha_ingreso' => now(),
        ]);

        return compact('despachador', 'cliente', 'ref');
    }

    /**
     * Genera un payload con fotos frescas. El token puede fijarse para simular
     * un reenvío del mismo intento.
     */
    private function payload(int $clienteId, int $refId, int $cantidad, ?string $token = null): array
    {
        return [
            'idempotency_key' => $token ?? (string) Str::uuid(),
            'cliente_id' => $clienteId,
            'fecha_salida' => now()->format('Y-m-d'),
            'conductor' => 'Wilmer Arango',
            'conductor_cedula' => '123456',
            'placa_vehiculo' => 'NQL-738',
            'transportador' => 'El Triunfo',
            'destino' => 'Cali',
            'observaciones' => 'Sin novedad',
            'detalles' => [
                ['referencia_id' => $refId, 'cantidad' => $cantidad],
            ],
            'foto_mercancia' => UploadedFile::fake()->image('mercancia.jpg'),
            'foto_conductor' => UploadedFile::fake()->image('conductor.jpg'),
        ];
    }

    /** US1 / C1 — Doble envío del mismo token crea una sola salida. */
    public function test_doble_envio_mismo_token_crea_una_sola_salida(): void
    {
        Storage::fake('public');
        ['despachador' => $d, 'cliente' => $c, 'ref' => $ref] = $this->escenario(10);
        $token = (string) Str::uuid();

        $this->actingAs($d)->post(route('salida.store'), $this->payload($c->id, $ref->id, 4, $token));
        $this->actingAs($d)->post(route('salida.store'), $this->payload($c->id, $ref->id, 4, $token));

        $this->assertSame(1, Tarja::count());
        $this->assertSame(1, OrdenCargue::count());
        $this->assertSame(1, MovimientoInventario::where('tipo', 'salida')->count());

        $ref->refresh();
        $this->assertSame(6, $ref->cantidad_actual); // descontado una sola vez

        $this->assertSame(1, IdempotencyKey::count());
        $this->assertSame(Tarja::first()->id, (int) IdempotencyKey::first()->resource_id);
    }

    /** US1 / C3 — Tokens distintos crean salidas distintas (no bloquea). */
    public function test_tokens_distintos_crean_salidas_distintas(): void
    {
        Storage::fake('public');
        ['despachador' => $d, 'cliente' => $c, 'ref' => $ref] = $this->escenario(10);

        $this->actingAs($d)->post(route('salida.store'), $this->payload($c->id, $ref->id, 2));
        $this->actingAs($d)->post(route('salida.store'), $this->payload($c->id, $ref->id, 3));

        $this->assertSame(2, Tarja::count());
        $ref->refresh();
        $this->assertSame(5, $ref->cantidad_actual); // 10 - 2 - 3
    }

    /** US1 / C4 — Sin token (o formato inválido) → error de validación, nada creado. */
    public function test_sin_token_es_rechazada(): void
    {
        Storage::fake('public');
        ['despachador' => $d, 'cliente' => $c, 'ref' => $ref] = $this->escenario(10);

        $data = $this->payload($c->id, $ref->id, 1);
        unset($data['idempotency_key']);
        $this->actingAs($d)->post(route('salida.store'), $data)
            ->assertSessionHasErrors('idempotency_key');

        $invalido = $this->payload($c->id, $ref->id, 1);
        $invalido['idempotency_key'] = 'no-es-uuid';
        $this->actingAs($d)->post(route('salida.store'), $invalido)
            ->assertSessionHasErrors('idempotency_key');

        $this->assertSame(0, Tarja::count());
    }

    /** US2 / C2 — El reenvío redirige a la ODC existente con mensaje informativo. */
    public function test_reenvio_redirige_a_odc_existente_con_info(): void
    {
        Storage::fake('public');
        ['despachador' => $d, 'cliente' => $c, 'ref' => $ref] = $this->escenario(10);
        $token = (string) Str::uuid();

        $this->actingAs($d)->post(route('salida.store'), $this->payload($c->id, $ref->id, 4, $token));
        $tarja = Tarja::first();

        $response = $this->actingAs($d)
            ->post(route('salida.store'), $this->payload($c->id, $ref->id, 4, $token));

        $response->assertRedirect(route('salida.show', $tarja));
        $response->assertSessionHas('info');
        $this->assertSame(1, Tarja::count());
    }

    /** US3 / C5 — Saldo insuficiente revierte todo y libera el token. */
    public function test_saldo_insuficiente_revierte_y_libera_token(): void
    {
        Storage::fake('public');
        ['despachador' => $d, 'cliente' => $c, 'ref' => $ref] = $this->escenario(5);

        $this->actingAs($d)
            ->post(route('salida.store'), $this->payload($c->id, $ref->id, 9))
            ->assertSessionHasErrors('detalles');

        $ref->refresh();
        $this->assertSame(5, $ref->cantidad_actual);
        $this->assertSame(0, Tarja::count());
        $this->assertSame(0, IdempotencyKey::count()); // token liberado por el rollback
    }

    /** US3 / SC-005 — El reenvío no produce doble descuento de inventario. */
    public function test_reenvio_no_descuenta_inventario_dos_veces(): void
    {
        Storage::fake('public');
        ['despachador' => $d, 'cliente' => $c, 'ref' => $ref] = $this->escenario(10);
        $token = (string) Str::uuid();

        $this->actingAs($d)->post(route('salida.store'), $this->payload($c->id, $ref->id, 4, $token));
        $this->actingAs($d)->post(route('salida.store'), $this->payload($c->id, $ref->id, 4, $token));

        $ref->refresh();
        $salidas = (int) $ref->movimientos()->where('tipo', 'salida')->sum('cantidad');
        $this->assertSame(4, $salidas);
        $this->assertSame(6, $ref->cantidad_actual);
    }
}
