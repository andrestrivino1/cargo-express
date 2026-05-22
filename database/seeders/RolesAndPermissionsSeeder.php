<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            'solicitudes.ver',
            'solicitudes.crear',
            'solicitudes.asignar',
            'gate-in.ver',
            'gate-in.crear',
            'referencias.ver',
            'referencias.crear',
            'vaciado.ver',
            'vaciado.programar',
            'vaciado.registrar-novedad',
            'inventario.ver',
            'inventario.ubicar',
            'gate-out.ver',
            'gate-out.crear',
            'entregas.ver',
            'entregas.crear',
            'entregas.generar-tarja',
            'reportes.ver',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Reset cache after creating permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create roles and assign permissions (use firstOrCreate for roles too)
        Role::create(['name' => 'cliente', 'guard_name' => 'web'])->givePermissionTo([
            'referencias.ver',
            'inventario.ver',
            'entregas.ver',
            'entregas.crear',
            'reportes.ver',
        ]);

        Role::create(['name' => 'portero'])->givePermissionTo([
            'gate-in.ver',
            'gate-in.crear',
            'gate-out.ver',
            'gate-out.crear',
        ]);

        Role::create(['name' => 'operador'])->givePermissionTo([
            'gate-in.ver',
            'referencias.ver',
            'referencias.crear',
            'vaciado.ver',
            'vaciado.registrar-novedad',
            'inventario.ver',
            'inventario.ubicar',
        ]);

        Role::create(['name' => 'coordinador'])->givePermissionTo([
            'solicitudes.ver',
            'solicitudes.asignar',
            'gate-in.ver',
        ]);

        Role::create(['name' => 'supervisor'])->givePermissionTo([
            'vaciado.ver',
            'vaciado.programar',
            'inventario.ver',
            'reportes.ver',
        ]);

        Role::create(['name' => 'despachador'])->givePermissionTo([
            'entregas.ver',
            'entregas.crear',
            'entregas.generar-tarja',
            'inventario.ver',
            'referencias.ver',
        ]);

        Role::create(['name' => 'gerente'])->givePermissionTo($permissions);

        Role::create(['name' => 'administrador'])->givePermissionTo($permissions);
    }
}