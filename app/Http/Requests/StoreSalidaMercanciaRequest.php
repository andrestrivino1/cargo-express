<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSalidaMercanciaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('salida.crear') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'cliente_id' => ['required', 'exists:users,id'],
            'nit' => ['nullable', 'string', 'max:30'],
            'fecha_salida' => ['required', 'date'],
            'conductor' => ['required', 'string', 'max:150'],
            'conductor_cedula' => ['nullable', 'string', 'max:20'],
            'placa_vehiculo' => ['required', 'string', 'max:20'],
            'transportador' => ['required', 'string', 'max:150'],
            'destino' => ['required', 'string', 'max:150'],
            'observaciones' => ['nullable', 'string'],

            'detalles' => ['required', 'array', 'min:1'],
            'detalles.*.referencia_id' => ['required', 'exists:referencias,id'],
            'detalles.*.cantidad' => ['required', 'integer', 'min:1'],

            'foto_mercancia' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:10240'],
            'foto_conductor' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:10240'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'cliente_id' => 'cliente',
            'placa_vehiculo' => 'placa del vehículo',
            'foto_mercancia' => 'foto de la mercancía',
            'foto_conductor' => 'foto del conductor',
        ];
    }
}
