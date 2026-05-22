<?php

namespace App\Http\Requests;

use App\Models\OrdenServicio;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreGateInRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'orden_servicio_id' => ['required', 'exists:ordenes_servicio,id'],
            'placa' => ['required', 'string', 'max:20'],
            'numero_contenedor' => ['required', 'string', 'max:20'],
            'estado_fisico' => ['nullable', 'string'],
            'notas' => ['nullable', 'string'],
            'fotos' => ['nullable', 'array'],
            'fotos.*' => ['image', 'mimes:jpg,png,webp', 'max:5120'],
            'documentos' => ['nullable', 'array'],
            'documentos.*' => ['file', 'mimes:pdf,doc,docx,xls,xlsx', 'max:10240'],
            'productos' => ['required', 'array', 'min:1'],
            'productos.*.producto_id' => ['required', 'exists:productos,id'],
            'productos.*.codigo' => ['nullable', 'string', 'max:100'],
            'productos.*.descripcion' => ['nullable', 'string', 'max:255'],
            'productos.*.cantidad' => ['required', 'integer', 'min:1'],
            'productos.*.unidad_medida' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($this->filled('orden_servicio_id')) {
                $orden = OrdenServicio::find($this->orden_servicio_id);

                if ($orden && $orden->estado->value !== 'activa') {
                    $validator->errors()->add(
                        'orden_servicio_id',
                        'La orden de servicio debe tener estado activa para registrar un ingreso.'
                    );
                }
            }
        });
    }
}