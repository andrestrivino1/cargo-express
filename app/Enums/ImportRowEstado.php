<?php

namespace App\Enums;

enum ImportRowEstado: string
{
    case Importado = 'importado';
    case Error = 'error';
    case Advertencia = 'advertencia';
    case Ignorado = 'ignorado';
}
