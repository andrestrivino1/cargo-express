<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VisibilidadModulosTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('administrador');

        return $user;
    }

    public function test_modulo_oculto_responde_404(): void
    {
        config(['modulos.transferencias' => false]);
        $admin = $this->admin();

        $this->actingAs($admin)->get('/transferencias')->assertNotFound();
    }

    public function test_modulo_visible_responde_ok(): void
    {
        config(['modulos.ingreso' => true]);
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('ingreso.index'))->assertOk();
    }

    public function test_reactivar_modulo_lo_hace_accesible(): void
    {
        $admin = $this->admin();

        config(['modulos.transferencias' => false]);
        $this->actingAs($admin)->get('/transferencias')->assertNotFound();

        config(['modulos.transferencias' => true]);
        $this->actingAs($admin)->get('/transferencias')->assertOk();
    }

    public function test_ocultar_y_reactivar_el_modulo_citas(): void
    {
        $admin = $this->admin();

        config(['modulos.citas' => false]);
        $this->actingAs($admin)->get('/citas')->assertNotFound();

        config(['modulos.citas' => true]);
        $this->actingAs($admin)->get('/citas')->assertOk();
    }

    public function test_ocultar_y_reactivar_el_modulo_porteria(): void
    {
        $admin = $this->admin();

        config(['modulos.porteria' => false]);
        $this->actingAs($admin)->get('/porteria')->assertNotFound();

        config(['modulos.porteria' => true]);
        $this->actingAs($admin)->get('/porteria')->assertOk();
    }
}
