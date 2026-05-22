<?php

namespace App\Http\Requests\Pendientes;

use Illuminate\Foundation\Http\FormRequest;

class CompletarSolicitudRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['administrador', 'coordinador']) === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'naviera' => ['nullable', 'string', 'max:100'],
            'puerto_origen' => ['nullable', 'string', 'max:100'],
            'descripcion' => ['nullable', 'string'],
        ];
    }
}
