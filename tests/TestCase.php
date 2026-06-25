<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Por defecto, todos los módulos están visibles en las pruebas para validar
     * que el código de los módulos ocultos sigue intacto (principio "ocultar, no
     * eliminar"). Las pruebas de visibilidad ajustan estas banderas explícitamente.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $modulos = array_map(fn () => true, config('modulos', []));
        config(['modulos' => $modulos]);
    }
}
