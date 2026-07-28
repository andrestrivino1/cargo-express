<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Permiso para eliminar un ingreso completo (con sus contenedores,
     * referencias, movimientos de entrada y archivos).
     *
     * Se concede a `operaciones` porque es el rol que opera el módulo día a día
     * y quien detecta los duplicados en el momento. El riesgo está acotado en el
     * servicio: solo se puede borrar un ingreso cuya mercancía siga intacta; en
     * cuanto algo se despachó, transfirió o vació, el borrado se bloquea.
     *
     * Se modela como permiso —y no con `role:` en la ruta— para poder quitarlo o
     * dárselo a otro rol sin tocar código. Idempotente.
     */
    public function up(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        Permission::firstOrCreate(['name' => 'ingreso.eliminar', 'guard_name' => 'web']);

        foreach (['administrador', 'gerente', 'operaciones'] as $rol) {
            $role = Role::where('name', $rol)->where('guard_name', 'web')->first();
            $role?->givePermissionTo('ingreso.eliminar');
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        Permission::where('name', 'ingreso.eliminar')->where('guard_name', 'web')->delete();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
