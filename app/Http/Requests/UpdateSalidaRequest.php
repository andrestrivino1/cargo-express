<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSalidaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return ($this->user()?->hasAnyRole(['administrador', 'coordinador']) === true)
            || ($this->user()?->can('salida.crear') ?? false);
    }

    /**
     * Solo datos de cabecera/transporte de la salida. No se editan los renglones
     * de mercancía para no alterar el inventario ya descontado.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nit' => ['nullable', 'string', 'max:30'],
            'fecha_salida' => ['required', 'date'],
            'conductor' => ['required', 'string', 'max:150'],
            'conductor_cedula' => ['nullable', 'string', 'max:20'],
            'placa_vehiculo' => ['required', 'string', 'max:20'],
            'transportador' => ['required', 'string', 'max:150'],
            'destino' => ['required', 'string', 'max:150'],
            'observaciones' => ['nullable', 'string'],

            // Reemplazo opcional de evidencias.
            'foto_mercancia' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:10240'],
            'foto_conductor' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:10240'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'placa_vehiculo' => 'placa del vehículo',
        ];
    }
}
