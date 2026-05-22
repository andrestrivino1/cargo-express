<?php

namespace App\Http\Requests\Importacion;

use Illuminate\Foundation\Http\FormRequest;

class SubirImportacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['administrador', 'coordinador']) === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'archivo' => ['required', 'file', 'mimes:xlsx', 'max:'.(int) config('importacion.max_file_size_kb', 51200)],
            'modo' => ['required', 'in:validar,importar'],
            'politica_duplicados' => ['required_if:modo,importar', 'in:omitir,actualizar_saldo,abortar'],
            'fecha_corte' => ['nullable', 'date'],
            'confirmar_clientes_autocreados' => ['required_if:modo,importar', 'accepted'],
        ];
    }

    public function archivoHash(): string
    {
        return hash_file('sha256', $this->file('archivo')->getRealPath());
    }
}
