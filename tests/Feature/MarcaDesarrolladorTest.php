<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Marca del desarrollador (ATRIO): aparece solo como crédito.
 *
 * "Desarrollado por ATRIO" va en el pie de la aplicación, en las pantallas de
 * acceso, en el ícono de la pestaña y en "Acerca del sistema". Cargo Express
 * conserva su nombre en la barra superior y en el login, y los documentos que
 * salen hacia el cliente (PDF) no llevan la marca.
 */
class MarcaDesarrolladorTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('administrador');

        return $user;
    }

    public function test_el_pie_acredita_al_desarrollador_sin_reemplazar_el_nombre_del_sistema(): void
    {
        $response = $this->actingAs($this->admin())->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('data-developer-credit', false);
        $response->assertSee('data-atrio-brand', false);
        $response->assertSee('Desarrollado por');
        $response->assertSee('Cargo Express. Todos los derechos reservados.');
        $response->assertSee('<i class="bi bi-box-seam-fill me-1"></i> Cargo Express', false);
        $response->assertSee('data-atrio-favicon', false);
    }

    public function test_el_menu_de_usuario_abre_acerca_del_sistema_con_la_version(): void
    {
        config(['app.version' => '9.9']);

        $response = $this->actingAs($this->admin())->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('data-about-link', false);
        $response->assertSee('data-about-modal', false);
        $response->assertSee('Acerca del sistema');
        $response->assertSee('Versión 9.9');
        $response->assertSee('Tecnología a la medida, desde la base.');
    }

    public function test_el_login_acredita_al_desarrollador_y_sigue_siendo_de_cargo_express(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
        $response->assertSee('data-developer-credit', false);
        $response->assertSee('data-atrio-favicon', false);
        $response->assertSee('CARGO EXPRESS');
        $response->assertDontSee('data-about-modal', false);
    }

    public function test_recuperar_password_tambien_lleva_el_credito(): void
    {
        $response = $this->get(route('password.request'));

        $response->assertOk();
        $response->assertSee('data-developer-credit', false);
        $response->assertSee('data-atrio-favicon', false);
    }

    public function test_los_documentos_pdf_no_llevan_la_marca_del_desarrollador(): void
    {
        $vistas = array_merge(
            glob(resource_path('views/pdf/*.blade.php')),
            glob(resource_path('views/manual/*.blade.php')),
            glob(resource_path('views/importacion/_reporte_pdf.blade.php')),
        );

        $this->assertNotEmpty($vistas);

        foreach ($vistas as $vista) {
            $contenido = file_get_contents($vista);

            $this->assertStringNotContainsStringIgnoringCase('atrio', $contenido, basename($vista));
            $this->assertStringNotContainsString('developer-credit', $contenido, basename($vista));
        }
    }
}
