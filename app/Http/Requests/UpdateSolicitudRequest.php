<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSolicitudRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['administrador', 'coordinador']) === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'cliente_id' => ['required', 'exists:users,id'],
            'numero_contenedor' => ['required', 'string', 'max:20'],
            'naviera' => ['nullable', 'string', 'max:100'],
            'puerto_origen' => ['nullable', 'string', 'max:100'],
            'descripcion' => ['nullable', 'string'],
            'fecha_solicitud' => ['required', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'fecha_solicitud.required' => 'Debes seleccionar la fecha de la solicitud.',
            'fecha_solicitud.date' => 'La fecha de la solicitud no es válida.',
        ];
    }
}
