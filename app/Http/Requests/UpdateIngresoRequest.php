<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

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

            // Imágenes del BL (aditivas, opcionales).
            'fotos' => ['nullable', 'array'],
            'fotos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],

            // Referencia nueva (opcional): solo se procesa si se diligencia el código.
            'nueva_referencia' => ['nullable', 'array'],
            'nueva_referencia.contenedor_id' => ['nullable', 'required_with:nueva_referencia.codigo', 'exists:contenedores,id'],
            'nueva_referencia.codigo' => ['nullable', 'string', 'max:100'],
            'nueva_referencia.descripcion' => ['nullable', 'required_with:nueva_referencia.codigo', 'string', 'max:255'],
            'nueva_referencia.unidad_medida' => ['nullable', 'required_with:nueva_referencia.codigo', 'string', 'max:50'],
            'nueva_referencia.cantidad' => ['nullable', 'required_with:nueva_referencia.codigo', 'integer', 'min:1'],
            'nueva_referencia.peso' => ['nullable', 'numeric', 'min:0'],
            'nueva_referencia.ubicacion_patio_id' => ['nullable', 'exists:ubicaciones_patio,id'],
        ];
    }

    /**
     * Valida que el contenedor destino de la referencia nueva pertenezca al ingreso
     * que se está editando (integridad: no se pueden agregar referencias a otro BL).
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $contenedorId = $this->input('nueva_referencia.contenedor_id');
            $ingreso = $this->route('ingreso');

            if ($contenedorId && $ingreso && ! $ingreso->contenedores()->whereKey($contenedorId)->exists()) {
                $validator->errors()->add(
                    'nueva_referencia.contenedor_id',
                    'El contenedor seleccionado no pertenece a este ingreso.'
                );
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
            'fotos.*' => 'imagen',
            'nueva_referencia.contenedor_id' => 'contenedor',
            'nueva_referencia.codigo' => 'código de referencia',
            'nueva_referencia.descripcion' => 'descripción',
            'nueva_referencia.unidad_medida' => 'unidad de medida',
            'nueva_referencia.cantidad' => 'cantidad',
        ];
    }
}
