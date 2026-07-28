<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Rol `operaciones`: registro documental de ingreso y salida de mercancía.
     * Recoge la responsabilidad que antes tenía el portero, que ahora solo hace
     * control en puerta. Idempotente.
     */
    public function up(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $rol = Role::firstOrCreate(['name' => 'operaciones', 'guard_name' => 'web']);

        $rol->givePermissionTo([
            'ingreso.ver',
            'ingreso.crear',
            'salida.ver',
            'salida.crear',
        ]);

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        Role::where('name', 'operaciones')->where('guard_name', 'web')->first()?->delete();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
