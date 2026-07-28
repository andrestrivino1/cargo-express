<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Rol `citas`: agenda la llegada física de contenedores ya declarados en un
     * ingreso. Recibe `ingreso.ver` (sin `ingreso.crear`) porque necesita
     * consultar los ingresos para elegir el contenedor a agendar, pero no puede
     * crearlos ni editarlos. Idempotente.
     */
    public function up(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $rol = Role::firstOrCreate(['name' => 'citas', 'guard_name' => 'web']);

        $rol->givePermissionTo([
            'citas.ver',
            'citas.crear',
            'citas.editar',
            'ingreso.ver',
        ]);

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        Role::where('name', 'citas')->where('guard_name', 'web')->first()?->delete();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
