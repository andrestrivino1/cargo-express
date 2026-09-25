<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * La raíz ya no sirve una página propia: desde la feature 010 redirige al
     * acceso de la aplicación. El recorrido completo (sesión activa, primer
     * login, registro cerrado) se verifica en EntradaSitioTest.
     */
    public function test_the_application_root_redirects_to_login(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/login');
    }
}
