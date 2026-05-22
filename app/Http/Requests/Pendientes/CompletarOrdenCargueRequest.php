<?php

namespace App\Http\Requests\Pendientes;

use Illuminate\Foundation\Http\FormRequest;

class CompletarOrdenCargueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['administrador', 'coordinador', 'despachador']) === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'despachador_id' => ['required', 'exists:users,id'],
            'notas' => ['nullable', 'string'],
        ];
    }
}
