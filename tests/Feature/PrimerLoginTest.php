<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pantallas de primer login (FR-024 a FR-026).
 *
 * Las vistas usaban `@extends('layouts.guest')`, pero ese layout es de componente
 * (`{{ $slot }}`) y no de herencia: renderizarlas reventaba con "Undefined variable
 * $slot". Como ForzarCambioPasswordYEmail manda ahí a todo usuario importado, ningún
 * cliente del inventario histórico podía entrar. Estos tests cubren ese renderizado.
 */
class PrimerLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function clienteImportado(bool $password, bool $email): User
    {
        $user = User::factory()->create(['name' => 'ADUVIDRIOS 116 SAS']);
        $user->assignRole('cliente');
        $user->forceFill([
            'requiere_cambio_password' => $password,
            'email_placeholder' => $email,
        ])->save();

        return $user;
    }

    public function test_la_pantalla_de_cambio_de_password_renderiza(): void
    {
        $user = $this->clienteImportado(password: true, email: true);

        $this->actingAs($user)->get(route('primer-login.password'))
            ->assertOk()
            ->assertSee('Cambia tu contraseña', false);
    }

    public function test_la_pantalla_de_actualizar_email_renderiza(): void
    {
        $user = $this->clienteImportado(password: false, email: true);

        $this->actingAs($user)->get(route('primer-login.email'))
            ->assertOk()
            ->assertSee('Actualiza tu email', false);
    }

    public function test_la_nueva_password_no_exige_simbolo(): void
    {
        $user = $this->clienteImportado(password: true, email: false);
        $user->forceFill(['password' => bcrypt('Cliente2026!')])->save();

        $this->actingAs($user)->post(route('primer-login.password.update'), [
            'password_actual' => 'Cliente2026!',
            'password' => 'Vidrios2026',
            'password_confirmation' => 'Vidrios2026',
        ])->assertRedirect(route('dashboard'))->assertSessionHasNoErrors();

        $this->assertFalse($user->fresh()->requiere_cambio_password);
    }

    public function test_el_cliente_importado_es_desviado_del_dashboard(): void
    {
        $user = $this->clienteImportado(password: true, email: true);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertRedirect(route('primer-login.password'));
    }
}
