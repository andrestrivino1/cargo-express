<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReferenciaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'referencias' => ['required', 'array', 'min:1'],
            'referencias.*.producto_id' => ['required', 'exists:productos,id'],
            'referencias.*.codigo' => ['required', 'string', 'max:100'],
            'referencias.*.descripcion' => ['nullable', 'string', 'max:255'],
            'referencias.*.cantidad' => ['required', 'integer', 'min:1'],
            'referencias.*.unidad_medida' => ['nullable', 'string', 'max:50'],
        ];
    }
}