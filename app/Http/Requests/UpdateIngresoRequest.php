<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateIngresoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['administrador', 'coordinador']) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'bl' => ['required', 'string', 'max:100'],
            'cliente_id' => ['required', 'exists:users,id'],
            'fecha_ingreso' => ['required', 'date', 'before_or_equal:today'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'bl' => 'BL',
            'cliente_id' => 'cliente',
            'fecha_ingreso' => 'fecha de ingreso',
        ];
    }
}
