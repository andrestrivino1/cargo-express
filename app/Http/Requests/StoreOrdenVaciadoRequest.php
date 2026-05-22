<?php

namespace App\Http\Requests;

use App\Enums\ContenedorEstado;
use App\Models\Contenedor;
use Illuminate\Foundation\Http\FormRequest;

class StoreOrdenVaciadoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'contenedor_id' => ['required', 'exists:contenedores,id'],
            'fecha_programada' => ['required', 'date', 'after:today'],
            'notas' => ['nullable', 'string'],
            'fotos' => ['nullable', 'array'],
            'fotos.*' => ['image', 'mimes:jpg,png,webp', 'max:5120'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->contenedor_id) {
                $contenedor = Contenedor::find($this->contenedor_id);

                if ($contenedor && $contenedor->estado !== ContenedorEstado::EnPatio) {
                    $validator->errors()->add(
                        'contenedor_id',
                        'El contenedor debe estar en estado "En Patio" para programar un vaciado.'
                    );
                }
            }
        });
    }
}