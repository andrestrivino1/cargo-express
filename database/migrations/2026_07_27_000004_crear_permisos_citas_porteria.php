<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Crea los permisos de los módulos nuevos (citas y portería) y los concede a
     * los roles que hoy tienen acceso total. Idempotente: se puede correr varias
     * veces sin duplicar ni romper datos.
     *
     * `gerente` los recibe aunque sea un rol retirado del selector: el retiro es
     * de la asignación, no de las capacidades. Si no los recibiera, dejaría de
     * tener acceso total sin que nadie lo haya decidido.
     */
    public function up(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $nuevos = [
            'citas.ver',
            'citas.crear',
            'citas.editar',
            'porteria.ver',
            'porteria.registrar',
        ];

        foreach ($nuevos as $nombre) {
            Permission::firstOrCreate(['name' => $nombre, 'guard_name' => 'web']);
        }

        foreach (['administrador', 'gerente'] as $rol) {
            $role = Role::where('name', $rol)->where('guard_name', 'web')->first();
            $role?->givePermissionTo($nuevos);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        Permission::whereIn('name', [
            'citas.ver',
            'citas.crear',
            'citas.editar',
            'porteria.ver',
            'porteria.registrar',
        ])->where('guard_name', 'web')->delete();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
