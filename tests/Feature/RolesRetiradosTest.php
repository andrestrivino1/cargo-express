<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Los roles retirados dejan de ofrecerse, pero NO se borran: quien ya los tiene
 * sigue operando igual hasta que un administrador lo reasigne.
 */
class RolesRetiradosTest extends TestCase
{
    use RefreshDatabase;

    private const RETIRADOS = ['coordinador', 'despachador', 'gerente', 'operador'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole('administrador');

        return $admin;
    }

    public function test_el_selector_de_roles_no_ofrece_los_retirados(): void
    {
        $respuesta = $this->actingAs($this->admin())->get(route('admin.usuarios.create'));

        $respuesta->assertOk();

        $roles = $respuesta->viewData('roles')->pluck('name')->all();

        foreach (self::RETIRADOS as $retirado) {
            $this->assertNotContains($retirado, $roles, "El rol retirado `{$retirado}` no debería ofrecerse.");
        }
    }

    public function test_el_selector_si_ofrece_los_roles_vigentes(): void
    {
        $respuesta = $this->actingAs($this->admin())->get(route('admin.usuarios.create'));

        $roles = $respuesta->viewData('roles')->pluck('name')->all();

        foreach (['citas', 'operaciones', 'portero', 'supervisor', 'cliente', 'administrador'] as $vigente) {
            $this->assertContains($vigente, $roles);
        }
    }

    public function test_asignar_un_rol_retirado_directamente_es_rechazado(): void
    {
        foreach (self::RETIRADOS as $retirado) {
            $this->actingAs($this->admin())
                ->post(route('admin.usuarios.store'), [
                    'name' => 'Prueba '.$retirado,
                    'email' => "prueba-{$retirado}@test.local",
                    'password' => 'Password123!',
                    'password_confirmation' => 'Password123!',
                    'role' => $retirado,
                ])
                ->assertSessionHasErrors('role');
        }

        $this->assertSame(0, User::where('email', 'like', 'prueba-%@test.local')->count());
    }

    public function test_un_usuario_preexistente_con_rol_retirado_conserva_sus_permisos(): void
    {
        // Un coordinador que ya existía antes del cambio.
        $coordinador = User::factory()->create();
        $coordinador->assignRole('coordinador');

        $this->assertTrue($coordinador->hasRole('coordinador'));
        $this->assertTrue($coordinador->can('ingreso.crear'));

        // Sigue operando: no se le bloquea el acceso ni se le migra el rol.
        $this->actingAs($coordinador)->get(route('ingreso.index'))->assertOk();
        $this->actingAs($coordinador)->get(route('salida.index'))->assertOk();
    }

    public function test_los_roles_retirados_siguen_existiendo_en_base_de_datos(): void
    {
        foreach (self::RETIRADOS as $retirado) {
            $this->assertDatabaseHas('roles', ['name' => $retirado, 'guard_name' => 'web']);
        }
    }

    public function test_el_listado_de_usuarios_marca_los_roles_retirados(): void
    {
        $coordinador = User::factory()->create(['name' => 'Ana Coordinadora']);
        $coordinador->assignRole('coordinador');

        $respuesta = $this->actingAs($this->admin())->get(route('admin.usuarios.index'));

        $respuesta->assertOk();
        $this->assertSame(self::RETIRADOS, $respuesta->viewData('rolesRetirados'));
        $respuesta->assertSee('Rol retirado de circulación', false);
    }

    public function test_el_administrador_conserva_la_edicion_administrativa(): void
    {
        // FR-043: retirar `coordinador` no puede dejar inaccesible una función
        // que estaba reservada a administrador|coordinador.
        $admin = $this->admin();

        $this->assertTrue($admin->hasRole('administrador'));
        $this->actingAs($admin)->get(route('admin.usuarios.index'))->assertOk();
        $this->actingAs($admin)->get(route('ingreso.index'))->assertOk();
    }

    public function test_reactivar_un_rol_lo_devuelve_al_selector(): void
    {
        // El retiro es reversible editando config/roles.php.
        config(['roles.retirados' => ['despachador']]);

        $roles = $this->actingAs($this->admin())
            ->get(route('admin.usuarios.create'))
            ->viewData('roles')
            ->pluck('name')
            ->all();

        $this->assertContains('coordinador', $roles);
        $this->assertNotContains('despachador', $roles);
    }
}
