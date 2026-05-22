<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bloquea el acceso a cualquier ruta autenticada mientras el usuario tenga
 * pendiente cambiar password genérica o actualizar email placeholder
 * (FR-024 a FR-026, R8).
 *
 * Excluidas: las rutas de /primer-login/* y logout para no entrar en loop.
 */
class ForzarCambioPasswordYEmail
{
    /** Rutas (por nombre) que siempre pasan sin redirigir. */
    private const RUTAS_LIBRES = [
        'logout',
        'primer-login.password',
        'primer-login.password.update',
        'primer-login.email',
        'primer-login.email.update',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user === null) {
            return $next($request);
        }

        if (in_array($request->route()?->getName(), self::RUTAS_LIBRES, true)) {
            return $next($request);
        }

        if ($user->requiere_cambio_password) {
            return redirect()->route('primer-login.password');
        }

        if ($user->email_placeholder) {
            return redirect()->route('primer-login.email');
        }

        return $next($request);
    }
}
