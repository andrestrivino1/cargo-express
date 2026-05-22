<?php

namespace Tests\Unit\Services\Importacion;

use App\Services\Importacion\UbicacionResolver;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class UbicacionResolverTest extends TestCase
{
    /** @return array<string, array{0:?string, 1:string, 2:string, 3:bool}> */
    public static function casos(): array
    {
        return [
            'sin espacio entre Modulo y número' => ['Modulo6 Bloque B', '6', 'B', true],
            'espacios estándar' => ['Modulo 2 Bloque A', '2', 'A', true],
            'guion entre módulo y bloque' => ['Modulo 3-Bloque C', '3', 'C', true],
            'guion bloque' => ['Modulo 2-Bloque A', '2', 'A', true],
            'con acento' => ['Módulo 4 Bloque D', '4', 'D', true],
            'texto no reconocido' => ['Patio Principal', 'Patio Principal', 'S/N', false],
            'vacío' => [null, 'SIN_UBICACION', 'S/N', false],
            'solo espacios' => ['   ', 'SIN_UBICACION', 'S/N', false],
        ];
    }

    #[Test]
    #[DataProvider('casos')]
    public function normaliza_distintas_notaciones(?string $raw, string $modulo, string $posicion, bool $normalizada): void
    {
        $r = (new UbicacionResolver)->resolverONormalizar($raw);

        self::assertSame($modulo, $r['modulo']);
        self::assertSame($posicion, $r['posicion']);
        self::assertSame($normalizada, $r['normalizada']);
    }
}
