<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Crea los permisos de los módulos nuevos (ingreso/salida) y los asigna a los
     * roles ya existentes en la base de datos. Idempotente: se puede correr varias
     * veces sin duplicar ni romper datos.
     */
    public function up(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $nuevos = ['ingreso.ver', 'ingreso.crear', 'salida.ver', 'salida.crear'];

        foreach ($nuevos as $nombre) {
            Permission::firstOrCreate(['name' => $nombre, 'guard_name' => 'web']);
        }

        // Asignación por rol (solo si el rol existe).
        $asignaciones = [
            'administrador' => $nuevos,
            'gerente' => $nuevos,
            'coordinador' => $nuevos,
            'operador' => ['ingreso.ver', 'ingreso.crear'],
            'despachador' => ['salida.ver', 'salida.crear'],
        ];

        foreach ($asignaciones as $rol => $permisos) {
            $role = Role::where('name', $rol)->where('guard_name', 'web')->first();
            $role?->givePermissionTo($permisos);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (['ingreso.ver', 'ingreso.crear', 'salida.ver', 'salida.crear'] as $nombre) {
            Permission::where('name', $nombre)->where('guard_name', 'web')->delete();
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
