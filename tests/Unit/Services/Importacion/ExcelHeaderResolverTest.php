<?php

namespace Tests\Unit\Services\Importacion;

use App\Services\Importacion\ExcelHeaderResolver;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ExcelHeaderResolverTest extends TestCase
{
    private ExcelHeaderResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = new ExcelHeaderResolver;
    }

    #[Test]
    public function reconoce_encabezado_estandar(): void
    {
        $primeraFila = [
            'fecha documentos', 'Modulo', 'Cliente', 'Mercancia', '#Referencia',
            'Detalle', 'Observación', 'Unidad', 'Contenedor', 'fecha deposito',
            'FECHA DE DESPACHO', 'DESPACHO', 'INVENTARIO',
        ];

        $map = $this->resolver->resolve($primeraFila);

        self::assertTrue($map->tieneTodasLasColumnasRequeridas());
        self::assertSame(2, $map->indice('cliente'));
        self::assertSame(4, $map->indice('referencia'));
        self::assertSame(7, $map->indice('unidad'));
        self::assertSame(8, $map->indice('contenedor'));
        self::assertSame(9, $map->indice('fecha_deposito'));
        self::assertSame(12, $map->indice('inventario_fisico'));
        self::assertCount(1, $map->paresDespacho);
        self::assertSame(['fecha' => 10, 'despacho' => 11], $map->paresDespacho[0]);
    }

    #[Test]
    public function tolera_columna_en_blanco_al_inicio_y_sin_mercancia(): void
    {
        $primeraFila = [
            '', 'fecha documento', 'Ubicación', 'Cliente', '#Referencia',
            'Detalle', 'Observación', 'Unidad', 'Contenedor', 'Fecha',
            'FECHA DE DESPACHO', 'DESPACHO', 'Inventario fisico',
        ];

        $map = $this->resolver->resolve($primeraFila);

        self::assertTrue($map->tieneTodasLasColumnasRequeridas(), implode(',', $map->columnasFaltantes));
        self::assertSame(3, $map->indice('cliente'));
        self::assertSame(12, $map->indice('inventario_fisico'));
        self::assertNull($map->indice('mercancia'));
    }

    #[Test]
    public function detecta_columnas_obligatorias_faltantes(): void
    {
        $primeraFila = ['fecha', 'modulo', 'mercancia', 'detalle', 'inventario'];

        $map = $this->resolver->resolve($primeraFila);

        self::assertFalse($map->tieneTodasLasColumnasRequeridas());
        self::assertContains('cliente', $map->columnasFaltantes);
        self::assertContains('referencia', $map->columnasFaltantes);
        self::assertContains('unidad', $map->columnasFaltantes);
        self::assertContains('contenedor', $map->columnasFaltantes);
        self::assertContains('fecha_deposito', $map->columnasFaltantes);
    }

    #[Test]
    public function factory_glass_sas_caso_real_fecha_documento_mas_Fecha_suelta(): void
    {
        // FACTORY GLASS SAS del archivo INVENTARIO TOTAL CONTROLCARGA 27022026.xlsx:
        // primera columna en blanco + "fecha documento" + "Fecha" como col de depósito.
        $primeraFila = [
            '', 'fecha documento', 'Ubicación', 'Cliente', 'Mercancia', '#Referencia',
            'Detalle', 'Observación', 'Unidad', 'Contenedor', 'Fecha',
            'FECHA DE DESPACHO', 'DESPACHO', 'FECHA DE DESPACHO', 'DESPACHO',
        ];

        $map = $this->resolver->resolve($primeraFila);

        self::assertTrue($map->tieneTodasLasColumnasRequeridas(), 'Faltantes: '.implode(',', $map->columnasFaltantes));
        self::assertSame(10, $map->indice('fecha_deposito')); // col "Fecha" (índice 0-based)
        self::assertSame(1, $map->indice('fecha_documento')); // col "fecha documento"
    }

    #[Test]
    public function detecta_multiples_pares_de_despacho(): void
    {
        $primeraFila = [
            'Cliente', '#Referencia', 'Unidad', 'Contenedor', 'fecha deposito', 'Inventario',
            'FECHA DE DESPACHO', 'DESPACHO',
            'FECHA DE DESPACHO', 'DESPACHO',
            'FECHA DE DESPACHO', 'DESPACHO',
        ];

        $map = $this->resolver->resolve($primeraFila);

        self::assertCount(3, $map->paresDespacho);
    }
}
