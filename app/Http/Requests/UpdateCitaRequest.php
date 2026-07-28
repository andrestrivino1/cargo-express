<?php

namespace App\Http\Requests;

use App\Enums\CitaCondicion;
use App\Enums\TamanoContenedor;
use App\Enums\TipoContenedor;
use App\Models\Contenedor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateCitaRequest extends FormRequest
{
    /**
     * Además del permiso, una cita atendida no se edita: es terminal.
     */
    public function authorize(): bool
    {
        if (! ($this->user()?->can('citas.editar') ?? false)) {
            return false;
        }

        return $this->route('cita')?->puedeEditarse() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'ingreso_id' => ['required', 'exists:ingresos,id'],
            'contenedor_id' => ['required', 'exists:contenedores,id'],
            'tipo' => ['required', Rule::enum(TipoContenedor::class)],
            'tamano' => ['required', Rule::enum(TamanoContenedor::class)],
            'condicion' => ['required', Rule::enum(CitaCondicion::class)],
            'fecha_esperada' => ['required', 'date'],
            'conductor_nombre' => ['required', 'string', 'max:150'],
            'conductor_cedula' => ['required', 'string', 'max:40'],
            'placa' => ['required', 'string', 'max:20'],
            'empresa' => ['required', 'string', 'max:150'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $ingresoId = $this->input('ingreso_id');
            $contenedorId = $this->input('contenedor_id');

            if (! $ingresoId || ! $contenedorId) {
                return;
            }

            $pertenece = Contenedor::whereKey($contenedorId)
                ->where('ingreso_id', $ingresoId)
                ->exists();

            if (! $pertenece) {
                $validator->errors()->add('contenedor_id', 'El contenedor seleccionado no pertenece al ingreso indicado.');
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'ingreso_id' => 'ingreso',
            'contenedor_id' => 'contenedor',
            'tipo' => 'tipo de contenedor',
            'tamano' => 'tamaño',
            'condicion' => 'condición (full/vacío)',
            'fecha_esperada' => 'fecha posible de llegada',
            'conductor_nombre' => 'nombre del conductor',
            'conductor_cedula' => 'cédula del conductor',
            'placa' => 'placa del vehículo',
            'empresa' => 'nombre de la empresa',
        ];
    }
}
