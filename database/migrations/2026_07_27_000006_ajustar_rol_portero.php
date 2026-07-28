<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * El rol `portero` pasa a hacer SOLO control en puerta: gana los permisos de
     * portería y pierde todo acceso a ingreso y salida, incluida la consulta.
     *
     * Se revocan también `gate-in.*` y `gate-out.*`: en producción el portero los
     * conserva de la cadena vieja. Hoy no son alcanzables (esos módulos están
     * ocultos y responden 404), pero si alguna vez se reactivaran, el portero
     * recuperaría en silencio acceso a un módulo fuera de su alcance. Gate-In y
     * Gate-Out son la versión anterior de Ingreso y Salida, así que FR-026 los
     * cubre igual.
     *
     * ⚠️ DESPLIEGUE: esta migración debe aplicarse junto con el módulo Portero.
     * Si se aplica sola, el rol `portero` queda sin acceso a ningún módulo.
     *
     * Idempotente.
     */
    private const REVOCADOS = [
        'ingreso.ver',
        'ingreso.crear',
        'salida.ver',
        'salida.crear',
        'gate-in.ver',
        'gate-in.crear',
        'gate-out.ver',
        'gate-out.crear',
    ];

    public function up(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $portero = Role::where('name', 'portero')->where('guard_name', 'web')->first();

        $portero?->givePermissionTo(['porteria.ver', 'porteria.registrar']);

        // revokePermissionTo falla si el permiso no existe en la BD; se filtra a
        // los que realmente existen para que la migración corra en cualquier entorno.
        $existentes = Permission::whereIn('name', self::REVOCADOS)
            ->where('guard_name', 'web')
            ->pluck('name')
            ->all();

        if ($existentes !== []) {
            $portero?->revokePermissionTo($existentes);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $portero = Role::where('name', 'portero')->where('guard_name', 'web')->first();

        $portero?->revokePermissionTo(['porteria.ver', 'porteria.registrar']);

        $existentes = Permission::whereIn('name', self::REVOCADOS)
            ->where('guard_name', 'web')
            ->pluck('name')
            ->all();

        if ($existentes !== []) {
            $portero?->givePermissionTo($existentes);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
