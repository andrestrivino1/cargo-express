<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PrimerLoginForzadoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::findOrCreate('cliente', 'web');
    }

    private function clienteAutocreado(): User
    {
        $u = User::factory()->create([
            'password' => Hash::make('Generica2026!'),
            'requiere_cambio_password' => true,
            'email_placeholder' => true,
            'email' => 'cliente-x@cargo-express.placeholder',
        ]);
        $u->assignRole('cliente');

        return $u;
    }

    #[Test]
    public function dashboard_redirige_a_cambio_de_password_si_flag_activo(): void
    {
        $u = $this->clienteAutocreado();

        $this->actingAs($u)
            ->get(route('dashboard'))
            ->assertRedirect(route('primer-login.password'));
    }

    #[Test]
    public function tras_cambiar_password_redirige_a_actualizar_email(): void
    {
        $u = $this->clienteAutocreado();

        $this->actingAs($u)
            ->post(route('primer-login.password.update'), [
                'password_actual' => 'Generica2026!',
                'password' => 'NuevaSegura1!',
                'password_confirmation' => 'NuevaSegura1!',
            ])
            ->assertRedirect(route('primer-login.email'));

        $u->refresh();
        self::assertFalse($u->requiere_cambio_password);
        self::assertNotNull($u->password_actualizada_at);
    }

    #[Test]
    public function tras_actualizar_email_redirige_a_dashboard(): void
    {
        $u = $this->clienteAutocreado();
        $u->forceFill(['requiere_cambio_password' => false])->save();

        $this->actingAs($u)
            ->post(route('primer-login.email.update'), [
                'email' => 'real@cliente.com',
            ])
            ->assertRedirect(route('dashboard'));

        $u->refresh();
        self::assertFalse($u->email_placeholder);
        self::assertSame('real@cliente.com', $u->email);
    }
}
