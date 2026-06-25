<?php

if (! function_exists('modulo_visible')) {
    /**
     * Indica si un módulo está visible/habilitado en la navegación y rutas.
     */
    function modulo_visible(string $clave): bool
    {
        return (bool) config("modulos.$clave", false);
    }
}
