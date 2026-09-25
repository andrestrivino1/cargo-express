<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Feature 010 / US2 — permiso para retirar una referencia del inventario.
 *
 * Viaja como migración y no como seeder porque en producción no hay SSH: las
 * migraciones sí corren por el flujo de despliegue, `db:seed` no.
 *
 * Se concede a los mismos perfiles que hoy pueden editar referencias en
 * inventario (`administrador` y `coordinador`). Se modela como permiso —y no con
 * `role:` en la ruta— para poder dárselo a otro rol, por ejemplo `supervisor`,
 * sin tocar código. Idempotente.
 */
return new class extends Migration
{
    public function up(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        Permission::firstOrCreate(['name' => 'inventario.retirar', 'guard_name' => 'web']);

        foreach (['administrador', 'coordinador'] as $rol) {
            $role = Role::where('name', $rol)->where('guard_name', 'web')->first();
            $role?->givePermissionTo('inventario.retirar');
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        Permission::where('name', 'inventario.retirar')->where('guard_name', 'web')->delete();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
