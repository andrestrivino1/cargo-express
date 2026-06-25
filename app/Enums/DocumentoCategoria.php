<?php

namespace App\Enums;

enum DocumentoCategoria: string
{
    // Documentos del ingreso
    case Bl = 'bl';
    case Dim = 'dim';
    case ListaEmpaque = 'lista_empaque';

    // Fotos de evidencia de la salida
    case FotoMercancia = 'foto_mercancia';
    case FotoConductor = 'foto_conductor';

    public function label(): string
    {
        return match ($this) {
            self::Bl => 'BL',
            self::Dim => 'DIM',
            self::ListaEmpaque => 'Lista de empaque',
            self::FotoMercancia => 'Foto de la mercancía',
            self::FotoConductor => 'Foto del conductor',
        };
    }
}
