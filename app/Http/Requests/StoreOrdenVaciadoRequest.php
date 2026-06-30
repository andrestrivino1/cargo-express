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
            // Se permite elegir de la lista (contenedor_id) o escribir el número manualmente.
            'contenedor_id' => ['nullable', 'exists:contenedores,id'],
            'numero_contenedor' => ['nullable', 'string', 'max:20'],
            'fecha_programada' => ['required', 'date'],
            'notas' => ['nullable', 'string'],
            'fotos' => ['nullable', 'array'],
            'fotos.*' => ['image', 'mimes:jpg,png,webp', 'max:5120'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Debe venir uno de los dos: contenedor de la lista o número manual.
            if (! $this->contenedor_id && ! $this->filled('numero_contenedor')) {
                $validator->errors()->add(
                    'contenedor_id',
                    'Seleccione un contenedor de la lista o ingrese el número manualmente.'
                );
            }

            // Si eligió uno de la lista, debe estar "En Patio".
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
