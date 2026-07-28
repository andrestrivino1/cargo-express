<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * El supervisor concentra el vaciado y la definición de la ubicación física
     * de la mercancía.
     *
     * ⚠️ ORDEN: debe aplicarse ANTES o junto con el retiro de roles. Hoy
     * `operador` es el único rol —fuera de administrador y gerente— con
     * `inventario.ubicar`; retirarlo sin habilitar antes al supervisor dejaría la
     * capacidad de ubicar mercancía sin ningún rol vigente que la ejerza.
     *
     * Idempotente.
     */
    public function up(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $supervisor = Role::where('name', 'supervisor')->where('guard_name', 'web')->first();

        $supervisor?->givePermissionTo([
            'vaciado.registrar-novedad',
            'inventario.ubicar',
        ]);

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $supervisor = Role::where('name', 'supervisor')->where('guard_name', 'web')->first();

        $supervisor?->revokePermissionTo([
            'vaciado.registrar-novedad',
            'inventario.ubicar',
        ]);

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
