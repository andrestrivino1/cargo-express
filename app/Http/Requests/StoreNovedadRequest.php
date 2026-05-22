<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreNovedadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tipo' => ['required', 'in:averia,faltante,dano_visible'],
            'descripcion' => ['required', 'string'],
            'referencia_id' => ['nullable', 'exists:referencias,id'],
            'cantidad_afectada' => ['nullable', 'integer', 'min:1'],
            'fotos' => ['nullable', 'array'],
            'fotos.*' => ['image', 'mimes:jpg,png,webp', 'max:5120'],
        ];
    }
}