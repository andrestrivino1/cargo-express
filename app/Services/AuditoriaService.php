<?php

namespace App\Services;

use App\Models\CambioAuditoria;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AuditoriaService
{
    /**
     * Registra una entrada de auditoría con el diff de los atributos modificados
     * del modelo. Debe llamarse ANTES de persistir (cuando el modelo aún está "dirty").
     * Si no hay cambios reales, no inserta nada y devuelve null.
     */
    public function registrarCambios(Model $modelo, User $usuario): ?CambioAuditoria
    {
        $dirty = $modelo->getDirty();

        if ($dirty === []) {
            return null;
        }

        $cambios = [];
        foreach ($dirty as $campo => $nuevo) {
            $cambios[$campo] = [
                'anterior' => $modelo->getOriginal($campo),
                'nuevo' => $nuevo,
            ];
        }

        return CambioAuditoria::create([
            'auditable_type' => $modelo->getMorphClass(),
            'auditable_id' => $modelo->getKey(),
            'usuario_id' => $usuario->getKey(),
            'cambios' => $cambios,
        ]);
    }
}
