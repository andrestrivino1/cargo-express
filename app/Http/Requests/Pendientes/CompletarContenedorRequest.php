<?php

namespace App\Http\Requests\Pendientes;

use Illuminate\Foundation\Http\FormRequest;

class CompletarContenedorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['administrador', 'coordinador', 'portero']) === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'placa_vehiculo' => ['required', 'string', 'max:20'],
            'tipo' => ['nullable', 'in:20,40,40HC,45HC,OTRO'],
            'destino_salida' => ['nullable', 'string', 'max:100'],
            'numero' => ['nullable', 'string', 'max:20'],
            'notas_conflicto' => ['nullable', 'string', 'max:1000'],
            'cliente_correcto_id' => ['nullable', 'integer', 'exists:users,id'],
            'eliminar_duplicado' => ['nullable', 'boolean'],
        ];
    }
}
