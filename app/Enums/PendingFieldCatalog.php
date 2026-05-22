<?php

namespace App\Enums;

use App\Exceptions\Importacion\PendingFieldNotInCatalogException;
use App\Models\Contenedor;
use App\Models\OrdenCargue;
use App\Models\OrdenServicio;
use App\Models\Solicitud;
use App\Models\Tarja;
use App\Models\User;

final class PendingFieldCatalog
{
    /**
     * Catálogo único de campos que un registro importado puede tener en estado `PENDIENTE_HISTORICO`.
     * Ver contracts/pending-fields.md.
     */
    private const CATALOG = [
        Solicitud::class => ['naviera', 'puerto_origen', 'descripcion'],
        OrdenServicio::class => ['vehiculo', 'conductor', 'conductor_documento', 'cita_puerto'],
        Contenedor::class => ['placa_vehiculo', 'tipo', 'destino_salida', 'numero', 'notas_conflicto'],
        OrdenCargue::class => ['despachador_id', 'notas'],
        Tarja::class => ['despachador_id', 'observaciones', 'vehiculo', 'conductor'],
        User::class => ['email_real', 'phone'],
    ];

    /** @return string[] */
    public static function forType(string $morphClass): array
    {
        if (! array_key_exists($morphClass, self::CATALOG)) {
            throw new PendingFieldNotInCatalogException(
                "El tipo {$morphClass} no tiene catálogo de campos pendientes registrado."
            );
        }

        return self::CATALOG[$morphClass];
    }

    /** @param string[] $campos */
    public static function validarCampos(string $morphClass, array $campos): void
    {
        $catalogo = self::forType($morphClass);
        $invalidos = array_diff($campos, $catalogo);

        if ($invalidos !== []) {
            throw new PendingFieldNotInCatalogException(
                "Los siguientes campos no están en el catálogo de {$morphClass}: ".implode(', ', $invalidos)
            );
        }
    }
}
