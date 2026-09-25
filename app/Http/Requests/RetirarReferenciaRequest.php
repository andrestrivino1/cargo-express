<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Feature 010 / US2 — retiro de una referencia del inventario vigente.
 *
 * No recibe datos: el retiro se confirma, no se diligencia. La constancia de la
 * baja es quién la hizo (`retirado_por`) y cuándo (`deleted_at`), más el
 * movimiento de baja en el ledger.
 *
 * Existe por el `authorize()`: repite en el request la verificación de permiso
 * que ya hace el middleware de la ruta, siguiendo el patrón del resto del
 * proyecto.
 */
class RetirarReferenciaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('inventario.retirar') === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
