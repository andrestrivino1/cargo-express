<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Feature 010 / US3 — la dirección del sitio lleva al acceso de Cargo Express.
 *
 * Antes, la raíz servía la página de bienvenida genérica de Laravel, con su
 * logotipo, enlaces a documentación externa y un registro público de usuarios
 * que la operación no usa: las cuentas las crea el administrador.
 */
class EntradaSitioTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_visitante_sin_sesion_llega_al_login(): void
    {
        $this->get('/')->assertRedirect('/login');
    }

    public function test_el_login_es_la_pantalla_de_cargo_express(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
        $response->assertSee('Cargo Express', false);
        $response->assertDontSee('laravel.com/docs');
        $response->assertDontSee('laracasts.com');
    }

    public function test_un_usuario_con_sesion_termina_en_su_tablero(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/')->assertRedirect('/login');
        $this->actingAs($user)->get('/login')->assertRedirect(route('dashboard'));
    }

    public function test_un_usuario_con_primer_login_pendiente_va_a_cambiar_su_password(): void
    {
        $user = User::factory()->create(['requiere_cambio_password' => true]);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertRedirect(route('primer-login.password'));
    }

    public function test_una_sesion_expirada_aterriza_en_el_login_y_no_en_un_error(): void
    {
        $response = $this->get(route('dashboard'));

        $response->assertRedirect(route('login'));
    }

    public function test_no_existe_registro_publico_de_usuarios(): void
    {
        $this->assertFalse(Route::has('register'), 'El layout oculta el enlace con Route::has(\'register\').');

        $this->get('/register')->assertNotFound();

        $this->post('/register', [
            'name' => 'Intruso',
            'email' => 'intruso@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertNotFound();

        $this->assertDatabaseMissing('users', ['email' => 'intruso@example.com']);
        $this->assertGuest();
    }
}
