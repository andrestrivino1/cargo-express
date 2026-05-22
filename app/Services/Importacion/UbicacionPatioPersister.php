<?php

namespace App\Services\Importacion;

use App\Models\UbicacionPatio;
use Illuminate\Support\Collection;

/**
 * Resuelve UbicacionPatio en BD a partir del DTO de UbicacionResolver,
 * con caché en memoria por (modulo|posicion) para evitar SELECTs repetidos.
 */
final class UbicacionPatioPersister
{
    /** @var Collection<string, UbicacionPatio> */
    private Collection $cache;

    public function __construct()
    {
        $this->cache = collect();
    }

    /** @param array{modulo:string, posicion:string, normalizada:bool} $resuelta */
    public function obtenerOCrear(array $resuelta): UbicacionPatio
    {
        $clave = $resuelta['modulo'].'|'.$resuelta['posicion'];

        $cached = $this->cache->get($clave);
        if ($cached !== null) {
            return $cached;
        }

        $registro = UbicacionPatio::query()->firstOrCreate(
            ['modulo' => $resuelta['modulo'], 'posicion' => $resuelta['posicion']],
            ['activa' => true, 'descripcion' => $resuelta['normalizada'] ? null : 'Importada sin normalizar']
        );

        $this->cache->put($clave, $registro);

        return $registro;
    }
}
