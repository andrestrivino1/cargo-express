<?php

namespace App\Enums;

enum ContenedorEstado: string
{
    case Solicitado = 'solicitado';
    case EnPatio = 'en_patio';
    case EnVaciado = 'en_vaciado';
    case VaciadoCompletado = 'vaciado_completado';
    case FueraDePatio = 'fuera_de_patio';

    public function label(): string
    {
        return match ($this) {
            self::Solicitado => 'Solicitado',
            self::EnPatio => 'En Patio',
            self::EnVaciado => 'En Vaciado',
            self::VaciadoCompletado => 'Vaciado Completado',
            self::FueraDePatio => 'Fuera de Patio',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Solicitado => 'warning',
            self::EnPatio => 'info',
            self::EnVaciado => 'primary',
            self::VaciadoCompletado => 'success',
            self::FueraDePatio => 'secondary',
        };
    }
}