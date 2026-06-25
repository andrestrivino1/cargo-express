<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ModuloVisible
{
    /**
     * Bloquea el acceso a un módulo oculto devolviendo 404 (parece inexistente)
     * sin eliminar la ruta ni el controlador. Reactivable vía config/modulos.php.
     */
    public function handle(Request $request, Closure $next, string $modulo): Response
    {
        abort_unless(config("modulos.$modulo", false), 404);

        return $next($request);
    }
}
