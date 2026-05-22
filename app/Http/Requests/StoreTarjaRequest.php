<?php

namespace App\Http\Requests;

use App\Models\Referencia;
use Illuminate\Foundation\Http\FormRequest;

class StoreTarjaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'detalles' => ['required', 'array', 'min:1'],
            'detalles.*.referencia_id' => ['required', 'exists:referencias,id'],
            'detalles.*.cantidad_entregada' => ['required', 'integer', 'min:1'],
            'detalles.*.ubicacion_origen_id' => ['required', 'exists:ubicaciones_patio,id'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $detalles = $this->input('detalles', []);

            foreach ($detalles as $index => $detalle) {
                if (empty($detalle['referencia_id']) || empty($detalle['cantidad_entregada'])) {
                    continue;
                }

                $referencia = Referencia::find($detalle['referencia_id']);

                if ($referencia && $detalle['cantidad_entregada'] > $referencia->cantidad_actual) {
                    $validator->errors()->add(
                        "detalles.{$index}.cantidad_entregada",
                        "La cantidad entregada no puede superar el stock actual ({$referencia->cantidad_actual}) de la referencia {$referencia->codigo}."
                    );
                }
            }
        });
    }
}
