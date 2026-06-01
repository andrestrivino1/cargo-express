<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateReferenciaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['administrador', 'coordinador']) === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'codigo' => ['required', 'string', 'max:100'],
            'descripcion' => ['nullable', 'string'],
            'unidad_medida' => ['nullable', 'string', 'max:20'],
            'ubicacion_patio_id' => ['nullable', 'exists:ubicaciones_patio,id'],
            'fecha_ingreso' => ['nullable', 'date'],
        ];
    }
}
