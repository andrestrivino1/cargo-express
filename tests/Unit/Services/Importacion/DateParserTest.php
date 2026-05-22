<?php

namespace Tests\Unit\Services\Importacion;

use App\Services\Importacion\DateParser;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class DateParserTest extends TestCase
{
    /** @return array<string, array{0:string|int|null, 1:?string}> */
    public static function casosProvider(): array
    {
        return [
            'vacío' => ['', null],
            'null' => [null, null],
            'serial Excel 45000' => [45000, '2023-03-15'],
            'd/m/Y colombiano' => ['9/4/2026', '2026-04-09'],
            'd/m/Y con ceros' => ['13/02/2026', '2026-02-13'],
            'd-m-Y' => ['15-03-2026', '2026-03-15'],
            'd/m/y' => ['2/5/26', '2026-05-02'],
            'Y-m-d' => ['2026-02-27', '2026-02-27'],
            'basura' => ['XX', null],
            'fecha imposible' => ['32/13/2026', null],
        ];
    }

    #[Test]
    #[DataProvider('casosProvider')]
    public function parsea_distintos_formatos(string|int|null $input, ?string $esperado): void
    {
        $result = DateParser::parse($input);

        if ($esperado === null) {
            self::assertNull($result);
        } else {
            self::assertInstanceOf(CarbonImmutable::class, $result);
            self::assertSame($esperado, $result->toDateString());
        }
    }
}
