<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrdenServicioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

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