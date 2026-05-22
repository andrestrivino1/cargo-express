<?php

namespace App\Services\Importacion;

/**
 * DTO devuelto por ExcelHeaderResolver. Mapea nombre canónico → índice de columna (0-indexed).
 */
final class HeaderMap
{
    /**
     * @param  array<string, int>  $columnasObligatorias  nombre canónico => índice
     * @param  array<string, int>  $columnasOpcionales
     * @param  array<int, array{fecha:int, despacho:int}>  $paresDespacho  índices 0-based
     * @param  string[]  $columnasFaltantes
     * @param  string[]  $columnasNoReconocidas
     */
    public function __construct(
        public readonly array $columnasObligatorias,
        public readonly array $columnasOpcionales,
        public readonly array $paresDespacho,
        public readonly array $columnasFaltantes,
        public readonly array $columnasNoReconocidas,
    ) {}

    public function tieneTodasLasColumnasRequeridas(): bool
    {
        return $this->columnasFaltantes === [];
    }

    public function indice(string $canonica): ?int
    {
        return $this->columnasObligatorias[$canonica]
            ?? $this->columnasOpcionales[$canonica]
            ?? null;
    }
}
