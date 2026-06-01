<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrdenCargueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cliente_id' => ['required', 'exists:users,id'],
            'fecha_despacho' => ['required', 'date'],
            'notas' => ['nullable', 'string'],
        ];
    }
}
