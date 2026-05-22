<?php

namespace App\Services\Importacion;

use App\Enums\ImportRowEstado;

/**
 * Resultado de validar una fila del Excel.
 *
 * @phpstan-type Advertencia array{tipo:string, mensaje:string}
 */
final class RowValidationResult
{
    /**
     * @param  array<int, Advertencia>  $advertencias
     * @param  array<string, mixed>  $datosNormalizados
     */
    public function __construct(
        public readonly ImportRowEstado $estado,
        public readonly ?string $tipoError,
        public readonly string $mensaje,
        public readonly array $advertencias,
        public readonly array $datosNormalizados,
    ) {}

    public static function ok(array $datosNormalizados, array $advertencias = []): self
    {
        return new self(
            estado: $advertencias === [] ? ImportRowEstado::Importado : ImportRowEstado::Importado,
            tipoError: null,
            mensaje: 'OK',
            advertencias: $advertencias,
            datosNormalizados: $datosNormalizados,
        );
    }

    public static function error(string $tipo, string $mensaje): self
    {
        return new self(
            estado: ImportRowEstado::Error,
            tipoError: $tipo,
            mensaje: $mensaje,
            advertencias: [],
            datosNormalizados: [],
        );
    }

    public static function ignorada(string $tipo, string $mensaje): self
    {
        return new self(
            estado: ImportRowEstado::Ignorado,
            tipoError: $tipo,
            mensaje: $mensaje,
            advertencias: [],
            datosNormalizados: [],
        );
    }
}
