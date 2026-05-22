<?php

namespace App\Http\Requests\Pendientes;

use Illuminate\Foundation\Http\FormRequest;

class CompletarOrdenServicioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['administrador', 'coordinador']) === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'vehiculo' => ['required', 'string', 'max:20'],
            'conductor' => ['required', 'string', 'max:255'],
            'conductor_documento' => ['nullable', 'string', 'max:20'],
            'cita_puerto' => ['required', 'date'],
        ];
    }
}
