<?php

namespace App\Services\Importacion;

/**
 * Reconoce columnas por nombre (no por posición) y detecta los pares
 * (FECHA DE DESPACHO, DESPACHO). Tolerante a columnas en blanco al inicio
 * y a variantes de capitalización/acentos. Ver contracts/excel-schema.md.
 */
final class ExcelHeaderResolver
{
    private const ALIASES_OBLIGATORIAS = [
        'cliente' => ['cliente'],
        'referencia' => ['#referencia', 'referencia', 'ref'],
        'unidad' => ['unidad', 'unidades', 'cantidad'],
        'contenedor' => ['contenedor', '#contenedor', 'nro contenedor', 'numero contenedor', 'número contenedor'],
        'fecha_deposito' => ['fecha deposito', 'fecha depósito', 'fecha de deposito', 'fecha de depósito'],
        'inventario_fisico' => ['inventario fisico', 'inventario físico', 'inventario', 'saldo', 'saldo actual'],
    ];

    private const ALIASES_OPCIONALES = [
        'fecha_documento' => ['fecha documentos', 'fecha documento', 'fecha'],
        'ubicacion' => ['ubicación', 'ubicacion', 'modulo', 'módulo'],
        'mercancia' => ['mercancia', 'mercancía'],
        'detalle' => ['detalle', 'detalles'],
        'observacion' => ['observación', 'observacion', 'observaciones'],
    ];

    private const ALIAS_FECHA_DESPACHO = ['fecha de despacho', 'fecha despacho'];

    private const ALIAS_DESPACHO = ['despacho'];

    /** @param array<int, mixed> $primeraFila */
    public function resolve(array $primeraFila): HeaderMap
    {
        $normalizada = [];
        foreach ($primeraFila as $idx => $celda) {
            $normalizada[$idx] = $this->normalizar((string) ($celda ?? ''));
        }

        $obligatorias = $this->emparejar(self::ALIASES_OBLIGATORIAS, $normalizada);
        $opcionales = $this->emparejar(self::ALIASES_OPCIONALES, $normalizada);
        $faltantes = array_values(array_diff(array_keys(self::ALIASES_OBLIGATORIAS), array_keys($obligatorias)));

        // Fallback 1: si fecha_documento absorbió "fecha" pero faltaba fecha_deposito,
        // significa que la hoja solo tiene una columna "fecha" — la pasamos a fecha_deposito.
        if (in_array('fecha_deposito', $faltantes, true) && isset($opcionales['fecha_documento'])) {
            $idxFecha = $opcionales['fecha_documento'];
            if ($normalizada[$idxFecha] === 'fecha') {
                $obligatorias['fecha_deposito'] = $idxFecha;
                unset($opcionales['fecha_documento']);
                $faltantes = array_values(array_diff($faltantes, ['fecha_deposito']));
            }
        }

        // Fallback 2: si la hoja tiene "fecha documento" + otra columna llamada simplemente
        // "fecha" (caso FACTORY GLASS SAS), usar esa segunda como fecha_deposito.
        if (in_array('fecha_deposito', $faltantes, true)) {
            $tomadas = array_unique(array_merge(
                array_values($obligatorias),
                array_values($opcionales),
            ));
            foreach ($normalizada as $idx => $valor) {
                if ($valor === 'fecha' && ! in_array($idx, $tomadas, true)) {
                    $obligatorias['fecha_deposito'] = $idx;
                    $faltantes = array_values(array_diff($faltantes, ['fecha_deposito']));
                    break;
                }
            }
        }

        $pares = $this->detectarPares($normalizada);
        $reconocidas = array_values(array_unique(array_merge(
            array_values($obligatorias),
            array_values($opcionales),
            array_merge(...array_map(fn ($p) => [$p['fecha'], $p['despacho']], $pares))
        )));

        $noReconocidas = [];
        foreach ($normalizada as $idx => $valor) {
            if ($valor === '') {
                continue;
            }
            if (in_array($idx, $reconocidas, true)) {
                continue;
            }
            $noReconocidas[] = $valor;
        }

        return new HeaderMap(
            columnasObligatorias: $obligatorias,
            columnasOpcionales: $opcionales,
            paresDespacho: $pares,
            columnasFaltantes: $faltantes,
            columnasNoReconocidas: $noReconocidas,
        );
    }

    private function normalizar(string $celda): string
    {
        $valor = mb_strtolower(trim($celda));
        $valor = preg_replace('/\s+/u', ' ', $valor) ?? '';

        return $valor;
    }

    /**
     * @param  array<string, string[]>  $catalogoAliases
     * @param  array<int, string>  $normalizada
     * @return array<string, int>
     */
    private function emparejar(array $catalogoAliases, array $normalizada): array
    {
        $resultado = [];
        foreach ($catalogoAliases as $canonica => $aliases) {
            foreach ($normalizada as $idx => $valor) {
                if ($valor !== '' && in_array($valor, $aliases, true)) {
                    $resultado[$canonica] = $idx;
                    break;
                }
            }
        }

        return $resultado;
    }

    /**
     * @param  array<int, string>  $normalizada
     * @return array<int, array{fecha:int, despacho:int}>
     */
    private function detectarPares(array $normalizada): array
    {
        $pares = [];
        $indicesOrdenados = array_keys($normalizada);
        sort($indicesOrdenados);

        $i = 0;
        while ($i < count($indicesOrdenados)) {
            $idx = $indicesOrdenados[$i];
            $valor = $normalizada[$idx];

            if (in_array($valor, self::ALIAS_FECHA_DESPACHO, true)) {
                // Buscar el siguiente "despacho" no vacío.
                for ($j = $i + 1; $j < count($indicesOrdenados); $j++) {
                    $idxJ = $indicesOrdenados[$j];
                    if ($normalizada[$idxJ] === '') {
                        continue;
                    }
                    if (in_array($normalizada[$idxJ], self::ALIAS_DESPACHO, true)) {
                        $pares[] = ['fecha' => $idx, 'despacho' => $idxJ];
                        $i = $j;
                    }
                    break;
                }
            }
            $i++;
        }

        return array_slice($pares, 0, config('importacion.max_pares_despacho', 8));
    }
}
