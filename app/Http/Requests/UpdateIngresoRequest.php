<?php

namespace App\Http\Requests;

use App\Models\Ingreso;
use App\Models\Referencia;
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

            // Corrección de cantidades de las referencias ya registradas (feature 010).
            // La clave de cada entrada es el id de la referencia; el valor, la nueva
            // cantidad declarada. Lo que no se envía, no se toca.
            'referencias' => ['nullable', 'array'],
            'referencias.*' => ['integer', 'min:1'],

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
     * Dos controles de integridad que las reglas por sí solas no pueden hacer:
     *
     * 1. El contenedor destino de la referencia nueva debe pertenecer al ingreso
     *    que se está editando (no se agregan referencias a otro BL).
     * 2. Cada cantidad corregida debe ser de una referencia de este ingreso y no
     *    puede quedar por debajo de lo ya consumido de esa referencia.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $ingreso = $this->route('ingreso');

            $contenedorId = $this->input('nueva_referencia.contenedor_id');
            if ($contenedorId && $ingreso && ! $ingreso->contenedores()->whereKey($contenedorId)->exists()) {
                $validator->errors()->add(
                    'nueva_referencia.contenedor_id',
                    'El contenedor seleccionado no pertenece a este ingreso.'
                );
            }

            $this->validarCantidadesCorregidas($validator, $ingreso);
        });
    }

    /**
     * Sin este control, un `referencias[<id ajeno>]` alteraría mercancía de otro
     * BL: la clave del arreglo viene del cliente y no la cubre ninguna regla.
     */
    private function validarCantidadesCorregidas(Validator $validator, ?Ingreso $ingreso): void
    {
        $cantidades = $this->input('referencias');

        if ($ingreso === null || ! is_array($cantidades) || $cantidades === []) {
            return;
        }

        $referencias = Referencia::whereIn('contenedor_id', $ingreso->contenedores()->select('id'))
            ->whereIn('id', array_keys($cantidades))
            ->get()
            ->keyBy('id');

        foreach ($cantidades as $referenciaId => $cantidad) {
            $campo = "referencias.{$referenciaId}";

            if ($validator->errors()->has($campo)) {
                continue; // Ya falló el formato; no tiene sentido evaluar el resto.
            }

            $referencia = $referencias->get((int) $referenciaId);

            if ($referencia === null) {
                $validator->errors()->add($campo, 'La referencia no pertenece a este ingreso.');

                continue;
            }

            $consumido = $referencia->cantidad_inicial - $referencia->cantidad_actual;

            if ((int) $cantidad < $consumido) {
                $validator->errors()->add($campo, sprintf(
                    'No puede declarar menos de %d: ya se despacharon %d unidad(es) de la referencia %s.',
                    $consumido,
                    $consumido,
                    $referencia->codigo
                ));
            }
        }
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
            'referencias.*' => 'cantidad',
            'nueva_referencia.contenedor_id' => 'contenedor',
            'nueva_referencia.codigo' => 'código de referencia',
            'nueva_referencia.descripcion' => 'descripción',
            'nueva_referencia.unidad_medida' => 'unidad de medida',
            'nueva_referencia.cantidad' => 'cantidad',
        ];
    }
}
