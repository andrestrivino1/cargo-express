<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * El cliente queda limitado a consultar el almacenamiento de su propia
     * mercancía. Pierde entregas, reportes/trazabilidad y referencias.
     *
     * El aislamiento real entre clientes lo aplica InventarioService, que fuerza
     * el filtro por cliente ignorando lo que venga del request; esta migración
     * solo recorta la superficie de módulos accesibles.
     *
     * Idempotente.
     */
    public function up(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $cliente = Role::where('name', 'cliente')->where('guard_name', 'web')->first();

        $cliente?->revokePermissionTo([
            'referencias.ver',
            'entregas.ver',
            'entregas.crear',
            'reportes.ver',
        ]);

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $cliente = Role::where('name', 'cliente')->where('guard_name', 'web')->first();

        $cliente?->givePermissionTo([
            'referencias.ver',
            'entregas.ver',
            'entregas.crear',
            'reportes.ver',
        ]);

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
