<?php

namespace App\Services\Importacion;

use App\Models\Contenedor;
use App\Models\ImportBatch;
use App\Models\Referencia;
use App\Models\UbicacionPatio;
use App\Models\User;
use Carbon\CarbonImmutable;

/**
 * Crea la Referencia importada. cantidad_actual se persiste con el valor
 * literal de `Inventario físico` del Excel — sin recalcular (FR-030).
 * Asocia un Producto del catálogo (creándolo si no existe) a partir de
 * mercancia + #referencia + detalle.
 */
final class ReferenciaMapper
{
    public function __construct(
        private readonly ProductoResolver $productos,
    ) {}

    public function crear(
        Contenedor $contenedor,
        User $cliente,
        UbicacionPatio $ubicacion,
        array $datosFila,
        ImportBatch $batch,
    ): Referencia {
        $descripcion = $this->componerDescripcion(
            $datosFila['mercancia'] ?? '',
            $datosFila['referencia'] ?? '',
            $datosFila['detalle'] ?? '',
        );

        $producto = $this->productos->obtenerOCrear(
            $datosFila['mercancia'] ?? null,
            $datosFila['referencia'] ?? null,
            $datosFila['detalle'] ?? null,
        );

        /** @var CarbonImmutable $fechaIngreso */
        $fechaIngreso = $datosFila['fecha_deposito'];

        return Referencia::create([
            'contenedor_id' => $contenedor->getKey(),
            'cliente_id' => $cliente->getKey(),
            'producto_id' => $producto?->getKey(),
            'codigo' => (string) ($datosFila['referencia'] ?? ''),
            'descripcion' => $descripcion,
            'cantidad_inicial' => (int) $datosFila['unidad'],
            'cantidad_actual' => (int) $datosFila['inventario_fisico'],
            'unidad_medida' => 'unidades',
            'ubicacion_patio_id' => $ubicacion->getKey(),
            'fecha_ingreso' => $fechaIngreso,
        ]);
    }

    private function componerDescripcion(string $mercancia, string $referencia, string $detalle): string
    {
        $partes = array_filter([trim($mercancia), trim($referencia), trim($detalle)], fn ($p) => $p !== '');

        return implode(' / ', $partes) ?: 'Sin descripción';
    }
}
