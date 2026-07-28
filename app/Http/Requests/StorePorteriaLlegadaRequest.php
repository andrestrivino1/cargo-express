<?php

namespace App\Http\Requests;

use App\Enums\PorteriaFotoCategoria;
use Illuminate\Foundation\Http\FormRequest;

class StorePorteriaLlegadaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('porteria.registrar') ?? false;
    }

    /**
     * Las cuatro evidencias son obligatorias. Mismo límite de 10 MB que ya usa
     * el módulo de Salida, por consistencia.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $reglas = ['observaciones' => ['nullable', 'string', 'max:500']];

        foreach (PorteriaFotoCategoria::cases() as $categoria) {
            $reglas[$categoria->campo()] = ['required', 'image', 'mimes:jpg,jpeg,png', 'max:10240'];
        }

        return $reglas;
    }

    /**
     * Nombra cada evidencia en español para que el mensaje de error diga
     * exactamente cuál falta.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        $atributos = ['observaciones' => 'observaciones'];

        foreach (PorteriaFotoCategoria::cases() as $categoria) {
            $atributos[$categoria->campo()] = mb_strtolower($categoria->label());
        }

        return $atributos;
    }

    /**
     * Archivos subidos, indexados por el valor de la categoría, listos para el
     * servicio.
     *
     * @return array<string, \Illuminate\Http\UploadedFile>
     */
    public function evidencias(): array
    {
        $evidencias = [];

        foreach (PorteriaFotoCategoria::cases() as $categoria) {
            $evidencias[$categoria->value] = $this->file($categoria->campo());
        }

        return $evidencias;
    }
}
