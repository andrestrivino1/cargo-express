<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class PrimerLoginController extends Controller
{
    public function password(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        if (! $user->requiere_cambio_password) {
            return $user->email_placeholder
                ? redirect()->route('primer-login.email')
                : redirect()->route('dashboard');
        }

        return view('auth.primer-login-password');
    }

    public function actualizarPassword(Request $request): RedirectResponse
    {
        $request->validate([
            'password_actual' => ['required'],
            'password' => ['required', 'confirmed', Password::defaults()->mixedCase()->numbers()->symbols()],
        ]);

        $user = $request->user();

        if (! Hash::check($request->string('password_actual'), $user->password)) {
            return back()->withErrors(['password_actual' => 'La contraseña actual no coincide.']);
        }

        $user->forceFill([
            'password' => Hash::make($request->string('password')),
            'requiere_cambio_password' => false,
            'password_actualizada_at' => now(),
        ])->save();

        return $user->email_placeholder
            ? redirect()->route('primer-login.email')->with('status', 'Contraseña actualizada. Ahora confirma tu email.')
            : redirect()->route('dashboard')->with('status', 'Contraseña actualizada.');
    }

    public function email(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        if (! $user->email_placeholder) {
            return redirect()->route('dashboard');
        }

        return view('auth.primer-login-email');
    }

    public function actualizarEmail(Request $request): RedirectResponse
    {
        $user = $request->user();

        $request->validate([
            'email' => ['required', 'email', 'unique:users,email,'.$user->id],
        ]);

        $user->forceFill([
            'email' => $request->string('email'),
            'email_placeholder' => false,
            'email_verified_at' => null,
        ])->save();

        return redirect()->route('dashboard')->with('status', 'Email actualizado.');
    }
}
