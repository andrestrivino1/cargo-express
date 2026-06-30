<?php

namespace Tests\Feature;

use App\Models\Contenedor;
use App\Models\OrdenVaciado;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VaciadoContenedorManualTest extends TestCase
{
    use RefreshDatabase;

    private function sup(): User
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $u = User::factory()->create();
        $u->assignRole('supervisor');

        return $u;
    }

    public function test_contenedor_manual_crea_el_contenedor_y_la_orden(): void
    {
        Storage::fake('public');
        $r = $this->actingAs($this->sup())->post(route('vaciado.store'), [
            'numero_contenedor' => 'MEDU9999999',
            'fecha_programada' => now()->toDateString(),
        ]);
        $r->assertRedirect();
        $r->assertSessionHasNoErrors();
        $cont = Contenedor::where('numero', 'MEDU9999999')->first();
        $this->assertNotNull($cont);
        $this->assertSame($cont->id, OrdenVaciado::first()->contenedor_id);
    }

    public function test_sin_contenedor_ni_numero_falla(): void
    {
        $r = $this->actingAs($this->sup())->post(route('vaciado.store'), [
            'fecha_programada' => now()->toDateString(),
        ]);
        $r->assertSessionHasErrors('contenedor_id');
    }
}
