<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrdenVaciadoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['administrador', 'coordinador']) === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'fecha_programada' => ['required', 'date'],
            'supervisor_id' => ['required', 'exists:users,id'],
            'notas' => ['nullable', 'string'],
        ];
    }
}
