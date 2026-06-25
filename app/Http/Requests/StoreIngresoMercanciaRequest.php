<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

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
            'cliente_id' => ['required', 'exists:users,id'],
            'fecha_ingreso' => ['required', 'date', 'before_or_equal:today'],

            'documento_bl' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'documento_dim' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'documento_lista_empaque' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],

            'contenedores' => ['required', 'array', 'min:1'],
            'contenedores.*.numero' => ['required', 'string', 'max:20'],
            'contenedores.*.tipo_mercancia' => ['required', 'string', 'max:100'],
            'contenedores.*.referencias' => ['required', 'array', 'min:1'],
            'contenedores.*.referencias.*.codigo' => ['required', 'string', 'max:100'],
            'contenedores.*.referencias.*.descripcion' => ['required', 'string', 'max:255'],
            'contenedores.*.referencias.*.unidad_medida' => ['required', 'string', 'max:50'],
            'contenedores.*.referencias.*.peso' => ['nullable', 'numeric', 'min:0'],
            'contenedores.*.referencias.*.cantidad' => ['required', 'integer', 'min:1'],
            'contenedores.*.referencias.*.ubicacion_patio_id' => ['nullable', 'exists:ubicaciones_patio,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $contenedores = $this->input('contenedores', []);
            $numeros = array_filter(array_map(fn ($c) => $c['numero'] ?? null, $contenedores));
            if (count($numeros) !== count(array_unique(array_map('strtoupper', $numeros)))) {
                $validator->errors()->add('contenedores', 'No se permiten números de contenedor repetidos en el mismo ingreso.');
            }
        });
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
            'documento_bl' => 'documento BL',
            'documento_dim' => 'documento DIM',
            'documento_lista_empaque' => 'lista de empaque',
        ];
    }
}
