<?php

namespace App\Services\Importacion;

use Carbon\CarbonImmutable;

/**
 * Aplica las reglas de [contracts/excel-schema.md §Reglas por fila importable]
 * sobre cada fila. Devuelve un RowValidationResult tipado.
 */
final class RowValidator
{
    public const ERR_CLIENTE_NO_RESUELTO = 'CLIENTE_NO_RESUELTO';

    public const ERR_CONTENEDOR_FALTANTE = 'CONTENEDOR_FALTANTE';

    public const ERR_FECHA_INVALIDA = 'FECHA_INVALIDA';

    public const ERR_CANTIDAD_INVALIDA = 'CANTIDAD_INVALIDA';

    public const ADV_SALDO_INCONSISTENTE = 'SALDO_INCONSISTENTE';

    public const ADV_DESPACHO_INCOMPLETO = 'DESPACHO_INCOMPLETO';

    public const ADV_UBICACION_NO_NORMALIZADA = 'UBICACION_NO_NORMALIZADA';

    public const ADV_FECHA_DEPOSITO_INFERIDA = 'FECHA_DEPOSITO_INFERIDA';

    public const ADV_FECHA_FALLBACK_CORTE = 'FECHA_FALLBACK_CORTE';

    public const IGN_FILA_CONTINUACION = 'FILA_CONTINUACION';

    public const IGN_FILA_TOTAL = 'FILA_TOTAL';

    public const IGN_FILA_ESPURIA = 'FILA_ESPURIA';

    public const ADV_UNIDAD_INFERIDA_INVENTARIO = 'UNIDAD_INFERIDA_INVENTARIO';

    public const ADV_INVENTARIO_INFERIDO = 'INVENTARIO_INFERIDO';

    public const ADV_CONTENEDOR_NUMERO_PENDIENTE = 'CONTENEDOR_NUMERO_PENDIENTE';

    public const ADV_CONTENEDOR_CONFLICTO_CLIENTE = 'CONTENEDOR_CONFLICTO_CLIENTE';

    public function __construct(
        private readonly UbicacionResolver $ubicaciones,
        private readonly ?CarbonImmutable $fechaCorteFallback = null,
    ) {}

    /** @param array<int, mixed> $fila */
    public function validar(array $fila, HeaderMap $headers): RowValidationResult
    {
        // Detecta fila TOTAL en cualquier celda — son sumas manuales al final de cada hoja.
        if ($this->tieneCeldaConTotal($fila)) {
            return RowValidationResult::ignorada(self::IGN_FILA_TOTAL, 'Fila de TOTAL (suma manual al final de la hoja)');
        }

        $cliente = $this->celda($fila, $headers->indice('cliente'));
        $contenedorRaw = $this->celda($fila, $headers->indice('contenedor'));

        // Fila continuación: el usuario operativo omite cliente cuando esa fila
        // pertenece al mismo bloque del contenedor anterior (línea de detalle adicional,
        // observación suelta, etc.). No es un error — se omite silenciosamente.
        if ($cliente === '' && $contenedorRaw !== '') {
            return RowValidationResult::ignorada(self::IGN_FILA_CONTINUACION, 'Fila sin cliente (continuación del bloque anterior, observación o detalle suelto)');
        }

        // Fila espuria: cliente Y contenedor ambos vacíos. Es ruido del Excel
        // (separadores, observaciones sueltas, formato sin datos). Se omite silenciosamente.
        if ($cliente === '' && $contenedorRaw === '') {
            return RowValidationResult::ignorada(self::IGN_FILA_ESPURIA, 'Fila sin cliente ni contenedor (separador o ruido del Excel)');
        }

        if ($cliente === '') {
            return RowValidationResult::error(self::ERR_CLIENTE_NO_RESUELTO, 'Cliente vacío');
        }

        // Si el contenedor viene vacío pero hay cliente y otros datos, generamos
        // un placeholder y registramos pendiente para que el operador edite el
        // número real más adelante. Marca especial: numero comienza con 'PEND-'.
        if ($contenedorRaw === '') {
            $contenedor = ''; // marcador — el service generará PEND-{hash} al crear
        } else {
            $contenedor = $this->normalizarContenedor($contenedorRaw);
        }

        // Fecha de depósito en cascada: primero la columna dedicada, si está vacía intenta
        // fecha_documento, si tampoco hay intenta la primera FECHA DE DESPACHO disponible.
        // Solo se rechaza si NINGUNA fecha del Excel sirve.
        $fechaDepRaw = $this->celda($fila, $headers->indice('fecha_deposito'));
        $fechaDep = DateParser::parse($fechaDepRaw);
        $fechaFuente = 'fecha_deposito';

        if ($fechaDep === null) {
            $fechaDocRaw = $this->celda($fila, $headers->indice('fecha_documento'));
            $fechaDep = DateParser::parse($fechaDocRaw);
            $fechaFuente = 'fecha_documento';
        }

        if ($fechaDep === null) {
            foreach ($headers->paresDespacho as $par) {
                $alt = DateParser::parse($this->celda($fila, $par['fecha']));
                if ($alt !== null) {
                    $fechaDep = $alt;
                    $fechaFuente = 'primer_despacho';
                    break;
                }
            }
        }

        // Último fallback: la fecha de corte del batch. La fila entra al sistema
        // pero queda marcada con advertencia para que el operador sepa que el
        // saldo se asoció a una fecha sintética, no a una real del Excel.
        if ($fechaDep === null && $this->fechaCorteFallback !== null) {
            $fechaDep = $this->fechaCorteFallback;
            $fechaFuente = 'fecha_corte_batch';
        }

        if ($fechaDep === null) {
            return RowValidationResult::error(self::ERR_FECHA_INVALIDA, "Sin fecha utilizable (depósito/documento/despacho): '{$fechaDepRaw}'");
        }

        $unidadRaw = $this->celda($fila, $headers->indice('unidad'));
        $invRaw = $this->celda($fila, $headers->indice('inventario_fisico'));

        $unidadValida = is_numeric($unidadRaw) && (int) $unidadRaw > 0;
        $invValido = is_numeric($invRaw) && (int) $invRaw >= 0;

        // Pares de despacho extraídos temprano para poder inferir saldos faltantes.
        $advertencias = [];
        $pares = $this->extraerPares($fila, $headers, $advertencias);
        $sumaDespachos = array_sum(array_column($pares, 'cantidad'));

        // Inferencia de unidad cuando viene mal pero hay inventario válido
        if (! $unidadValida && $invValido) {
            $unidad = (int) $invRaw + $sumaDespachos;
            $advertencias[] = [
                'tipo' => self::ADV_UNIDAD_INFERIDA_INVENTARIO,
                'mensaje' => "Unidad inválida ('{$unidadRaw}'), se infirió como inventario_fisico + despachos = {$unidad}",
            ];
        } elseif (! $unidadValida) {
            return RowValidationResult::error(self::ERR_CANTIDAD_INVALIDA, "Cantidad (Unidad) inválida: '{$unidadRaw}'");
        } else {
            $unidad = (int) $unidadRaw;
        }

        // Inferencia de inventario_fisico cuando viene vacío: unidad - Σ despachos
        if (! $invValido && $invRaw === '') {
            $inventarioFisico = max(0, $unidad - $sumaDespachos);
            $advertencias[] = [
                'tipo' => self::ADV_INVENTARIO_INFERIDO,
                'mensaje' => "Inventario físico vacío, calculado como unidad ({$unidad}) − despachos ({$sumaDespachos}) = {$inventarioFisico}",
            ];
        } elseif (! $invValido) {
            return RowValidationResult::error(self::ERR_CANTIDAD_INVALIDA, "Inventario físico inválido: '{$invRaw}'");
        } else {
            $inventarioFisico = (int) $invRaw;
        }

        $ubicacion = $this->ubicaciones->resolverONormalizar($this->celda($fila, $headers->indice('ubicacion')));
        if (! $ubicacion['normalizada']) {
            $advertencias[] = ['tipo' => self::ADV_UBICACION_NO_NORMALIZADA, 'mensaje' => "Ubicación no reconocida: '{$ubicacion['modulo']}'"];
        }

        if ($fechaFuente === 'fecha_corte_batch') {
            $advertencias[] = [
                'tipo' => self::ADV_FECHA_FALLBACK_CORTE,
                'mensaje' => "Ninguna fecha del Excel utilizable, se usó fecha de corte del batch = {$fechaDep->toDateString()}",
            ];
        } elseif ($fechaFuente !== 'fecha_deposito') {
            $advertencias[] = [
                'tipo' => self::ADV_FECHA_DEPOSITO_INFERIDA,
                'mensaje' => "Fecha de depósito vacía, se usó {$fechaFuente} = {$fechaDep->toDateString()}",
            ];
        }

        if (($unidad - $sumaDespachos) !== $inventarioFisico) {
            $advertencias[] = [
                'tipo' => self::ADV_SALDO_INCONSISTENTE,
                'mensaje' => "Inconsistencia: unidades ({$unidad}) − Σ despachos ({$sumaDespachos}) ≠ inventario_fisico ({$inventarioFisico})",
            ];
        }

        if ($contenedor === '') {
            $advertencias[] = [
                'tipo' => self::ADV_CONTENEDOR_NUMERO_PENDIENTE,
                'mensaje' => 'Número de contenedor vacío; se asigna placeholder y queda pendiente de completar',
            ];
        }

        return RowValidationResult::ok([
            'cliente' => $cliente,
            'contenedor' => $contenedor,
            'fecha_deposito' => $fechaDep,
            'unidad' => $unidad,
            'inventario_fisico' => $inventarioFisico,
            'ubicacion' => $ubicacion,
            'pares_despacho' => $pares,
            'referencia' => $this->celda($fila, $headers->indice('referencia')),
            'mercancia' => $this->celda($fila, $headers->indice('mercancia')),
            'detalle' => $this->celda($fila, $headers->indice('detalle')),
            'observacion' => $this->celda($fila, $headers->indice('observacion')),
        ], $advertencias);
    }

    /** @param array<int, mixed> $fila */
    private function tieneCeldaConTotal(array $fila): bool
    {
        foreach ($fila as $valor) {
            $v = trim((string) ($valor ?? ''));
            if ($v === '') {
                continue;
            }
            // Match case-insensitive: "TOTAL", "Total", "TOTALES", " total ", etc.
            if (preg_match('/^\s*total(es)?\s*$/iu', $v) === 1) {
                return true;
            }
        }

        return false;
    }

    /** @param array<int, mixed> $fila */
    private function celda(array $fila, ?int $idx): string
    {
        if ($idx === null || ! array_key_exists($idx, $fila)) {
            return '';
        }

        return trim((string) ($fila[$idx] ?? ''));
    }

    private function normalizarContenedor(string $raw): string
    {
        $sinEspacios = preg_replace('/\s+/u', '', $raw) ?? '';

        return mb_strtoupper($sinEspacios);
    }

    /**
     * @param  array<int, mixed>  $fila
     * @param  array<int, array{tipo:string, mensaje:string}>  $advertencias  (modificado por referencia)
     * @return array<int, array{fecha:CarbonImmutable, cantidad:int}>
     */
    private function extraerPares(array $fila, HeaderMap $headers, array &$advertencias): array
    {
        $resultado = [];
        foreach ($headers->paresDespacho as $par) {
            $fechaRaw = $this->celda($fila, $par['fecha']);
            $cantRaw = $this->celda($fila, $par['despacho']);

            $tieneFecha = $fechaRaw !== '';
            $tieneCant = $cantRaw !== '';

            if (! $tieneFecha && ! $tieneCant) {
                continue;
            }

            if ($tieneFecha xor $tieneCant) {
                $advertencias[] = ['tipo' => self::ADV_DESPACHO_INCOMPLETO, 'mensaje' => "Par despacho incompleto: fecha='{$fechaRaw}', cantidad='{$cantRaw}'"];

                continue;
            }

            $fecha = DateParser::parse($fechaRaw);
            if ($fecha === null || ! is_numeric($cantRaw)) {
                $advertencias[] = ['tipo' => self::ADV_DESPACHO_INCOMPLETO, 'mensaje' => "Par despacho inválido: fecha='{$fechaRaw}', cantidad='{$cantRaw}'"];

                continue;
            }

            $resultado[] = ['fecha' => $fecha, 'cantidad' => (int) $cantRaw];
        }

        return $resultado;
    }
}
