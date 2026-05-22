<?php

namespace App\Services\Importacion;

use App\Models\Producto;
use Illuminate\Support\Collection;

/**
 * Resuelve (o crea) el Producto al que pertenece una Referencia importada.
 *
 * Composición del nombre:
 *   - Si hay Mercancia y #Referencia → "{Mercancia} - {#Referencia}"
 *   - Si solo hay una de las dos → usa esa
 *   - Si ninguna → null (la Referencia queda sin producto asociado)
 *
 * Identidad del Producto = (nombre, medidas). Misma combinación reusa el
 * registro existente — idempotente para reimports.
 *
 * Cache en memoria por hoja para no hacer SELECT por cada Referencia.
 */
final class ProductoResolver
{
    /** @var Collection<string, Producto> */
    private Collection $cache;

    public function __construct()
    {
        $this->cache = collect();
    }

    public function obtenerOCrear(?string $mercancia, ?string $referencia, ?string $detalle): ?Producto
    {
        $nombre = $this->componerNombre($mercancia, $referencia);
        if ($nombre === null) {
            return null;
        }

        $medidas = trim((string) ($detalle ?? '')) ?: null;
        $clave = mb_strtolower($nombre.'|'.((string) $medidas));

        $cached = $this->cache->get($clave);
        if ($cached !== null) {
            return $cached;
        }

        $producto = Producto::firstOrCreate(
            ['nombre' => $nombre, 'medidas' => $medidas],
            ['activo' => true],
        );

        $this->cache->put($clave, $producto);

        return $producto;
    }

    private function componerNombre(?string $mercancia, ?string $referencia): ?string
    {
        $m = trim((string) ($mercancia ?? ''));
        $r = trim((string) ($referencia ?? ''));

        if ($r !== '' && $m !== '') {
            return $m.' - '.$r;
        }

        return $r !== '' ? $r : ($m !== '' ? $m : null);
    }
}
