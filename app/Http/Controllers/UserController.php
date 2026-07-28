<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\RolesDisponibles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;

class UserController extends Controller
{
    public function __construct(
        private readonly RolesDisponibles $roles,
    ) {}

    public function index()
    {
        $usuarios = User::with('roles')
            ->orderBy('name')
            ->paginate(15);

        return view('admin.usuarios.index', [
            'usuarios' => $usuarios,
            // Permite señalar en el listado quién sigue con un rol retirado,
            // para que el administrador pueda reasignarlo.
            'rolesRetirados' => $this->roles->retirados(),
        ]);
    }

    public function create()
    {
        $roles = $this->roles->asignables();

        return view('admin.usuarios.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->reglas());

        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'phone'    => $validated['phone'] ?? null,
            'password' => Hash::make($validated['password']),
        ]);

        $user->assignRole($validated['role']);

        return redirect()->route('admin.usuarios.index')
            ->with('success', 'Usuario creado exitosamente.');
    }

    public function edit(User $usuario)
    {
        $roles = $this->roles->asignables();

        return view('admin.usuarios.edit', compact('usuario', 'roles'));
    }

    public function update(Request $request, User $usuario)
    {
        $validated = $request->validate($this->reglas($usuario->id));

        $usuario->update([
            'name'  => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
        ]);

        if (!empty($validated['password'])) {
            $usuario->update(['password' => Hash::make($validated['password'])]);
        }

        $usuario->syncRoles([$validated['role']]);

        return redirect()->route('admin.usuarios.index')
            ->with('success', 'Usuario actualizado exitosamente.');
    }

    public function destroy(User $usuario)
    {
        if ($usuario->hasRole('administrador')) {
            return redirect()->route('admin.usuarios.index')
                ->with('error', 'No se puede eliminar un usuario administrador.');
        }

        $usuario->delete();

        return redirect()->route('admin.usuarios.index')
            ->with('success', 'Usuario eliminado exitosamente.');
    }

    /**
     * Reglas comunes a crear y editar.
     *
     * `Rule::notIn` rechaza los roles retirados aunque la solicitud se envíe
     * directamente, sin pasar por el formulario.
     *
     * @return array<string, mixed>
     */
    private function reglas(?int $usuarioId = null): array
    {
        return [
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($usuarioId)],
            'password' => $usuarioId
                ? ['nullable', 'confirmed', Rules\Password::defaults()]
                : ['required', 'confirmed', Rules\Password::defaults()],
            'phone'    => ['nullable', 'string', 'max:20'],
            'role'     => [
                'required',
                'string',
                'exists:roles,name',
                Rule::notIn($this->roles->retirados()),
            ],
        ];
    }
}
