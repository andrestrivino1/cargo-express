<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePorteriaNovedadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('porteria.registrar') ?? false;
    }

    /**
     * Se exige al menos un identificador del vehículo: placa o contenedor.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'placa' => ['nullable', 'string', 'max:20', 'required_without:numero_contenedor'],
            'numero_contenedor' => ['nullable', 'string', 'max:20', 'required_without:placa'],
            'descripcion' => ['required', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'placa' => 'placa del vehículo',
            'numero_contenedor' => 'número de contenedor',
            'descripcion' => 'descripción de la novedad',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'placa.required_without' => 'Indica al menos la placa o el número de contenedor.',
            'numero_contenedor.required_without' => 'Indica al menos la placa o el número de contenedor.',
        ];
    }
}
