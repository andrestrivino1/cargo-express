<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreIngresoMercanciaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('ingreso.crear') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'bl' => ['required', 'string', 'max:100'],
            'numero_contenedor' => ['required', 'string', 'max:20'],
            'cliente_id' => ['required', 'exists:users,id'],
            'tipo_mercancia' => ['required', 'string', 'max:100'],

            'referencias' => ['required', 'array', 'min:1'],
            'referencias.*.codigo' => ['required', 'string', 'max:100'],
            'referencias.*.descripcion' => ['required', 'string', 'max:255'],
            'referencias.*.unidad_medida' => ['required', 'string', 'max:50'],
            'referencias.*.peso' => ['required', 'numeric', 'min:0'],
            'referencias.*.cantidad' => ['required', 'integer', 'min:1'],
            'referencias.*.ubicacion_patio_id' => ['required', 'exists:ubicaciones_patio,id'],

            'documento_bl' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'documento_dim' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'documento_lista_empaque' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'bl' => 'BL',
            'numero_contenedor' => 'contenedor',
            'cliente_id' => 'cliente',
            'tipo_mercancia' => 'tipo de mercancía',
            'documento_bl' => 'documento BL',
            'documento_dim' => 'documento DIM',
            'documento_lista_empaque' => 'lista de empaque',
        ];
    }
}
