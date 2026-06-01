<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGateOutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['administrador', 'coordinador']) === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'hora' => ['required', 'date'],
            'estado_fisico' => ['nullable', 'string'],
            'notas' => ['nullable', 'string'],
        ];
    }
}
