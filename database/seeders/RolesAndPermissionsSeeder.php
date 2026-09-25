<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Estado final de la matriz de roles y permisos, para instalaciones limpias.
     *
     * Es idempotente (firstOrCreate en roles y permisos): se puede correr sobre
     * una base que ya tiene los roles creados por las migraciones sin fallar.
     *
     * En producción los cambios de permisos viajan en las migraciones idempotentes
     * 2026_07_27_00000{4..9}, porque el hosting no tiene SSH para correr seeders.
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
            'ingreso.ver',
            'ingreso.crear',
            'ingreso.eliminar',
            'salida.ver',
            'salida.crear',
            'referencias.ver',
            'referencias.crear',
            'vaciado.ver',
            'vaciado.programar',
            'vaciado.registrar-novedad',
            'inventario.ver',
            'inventario.ubicar',
            'inventario.retirar', // Feature 010 — retiro de referencias del inventario
            'gate-out.ver',
            'gate-out.crear',
            'entregas.ver',
            'entregas.crear',
            'entregas.generar-tarja',
            'reportes.ver',
            // Feature 009 — módulos Citas y Portero
            'citas.ver',
            'citas.crear',
            'citas.editar',
            'porteria.ver',
            'porteria.registrar',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Reset cache after creating permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Cliente: solo consulta el almacenamiento de su propia mercancía.
        Role::firstOrCreate(['name' => 'cliente', 'guard_name' => 'web'])->givePermissionTo([
            'inventario.ver',
        ]);

        // Portero: solo control en puerta. No registra ingresos ni salidas.
        Role::firstOrCreate(['name' => 'portero', 'guard_name' => 'web'])->givePermissionTo([
            'porteria.ver',
            'porteria.registrar',
        ]);

        // Citas: agenda llegadas. Consulta ingresos solo para elegir el contenedor.
        Role::firstOrCreate(['name' => 'citas', 'guard_name' => 'web'])->givePermissionTo([
            'citas.ver',
            'citas.crear',
            'citas.editar',
            'ingreso.ver',
        ]);

        // Operaciones: registro documental de ingreso y salida de mercancía.
        // Puede eliminar ingresos (p. ej. duplicados por doble envío); el servicio
        // bloquea el borrado si la mercancía ya se movió.
        Role::firstOrCreate(['name' => 'operaciones', 'guard_name' => 'web'])->givePermissionTo([
            'ingreso.ver',
            'ingreso.crear',
            'ingreso.eliminar',
            'salida.ver',
            'salida.crear',
        ]);

        // Supervisor: vaciado + ubicación física de la mercancía.
        Role::firstOrCreate(['name' => 'supervisor', 'guard_name' => 'web'])->givePermissionTo([
            'vaciado.ver',
            'vaciado.programar',
            'vaciado.registrar-novedad',
            'inventario.ver',
            'inventario.ubicar',
            'reportes.ver',
        ]);

        // --- Roles retirados de circulación (config/roles.php) ---
        // Se siguen creando para no romper instalaciones ni histórico: los
        // usuarios ya asignados conservan sus permisos. Solo dejan de ofrecerse
        // al crear o editar un usuario.

        Role::firstOrCreate(['name' => 'operador', 'guard_name' => 'web'])->givePermissionTo([
            'gate-in.ver',
            'ingreso.ver',
            'ingreso.crear',
            'referencias.ver',
            'referencias.crear',
            'vaciado.ver',
            'vaciado.registrar-novedad',
            'inventario.ver',
            'inventario.ubicar',
        ]);

        Role::firstOrCreate(['name' => 'coordinador', 'guard_name' => 'web'])->givePermissionTo([
            'solicitudes.ver',
            'solicitudes.asignar',
            'gate-in.ver',
            'ingreso.ver',
            'ingreso.crear',
            'salida.ver',
            'salida.crear',
            'inventario.ver',
            'inventario.retirar', // Feature 010 — mismo alcance que ya tenía para editar referencias
            'reportes.ver',
        ]);

        Role::firstOrCreate(['name' => 'despachador', 'guard_name' => 'web'])->givePermissionTo([
            'entregas.ver',
            'entregas.crear',
            'entregas.generar-tarja',
            'salida.ver',
            'salida.crear',
            'inventario.ver',
            'referencias.ver',
        ]);

        Role::firstOrCreate(['name' => 'gerente', 'guard_name' => 'web'])->givePermissionTo($permissions);

        Role::firstOrCreate(['name' => 'administrador', 'guard_name' => 'web'])->givePermissionTo($permissions);
    }
}
