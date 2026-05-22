<?php

namespace Tests\Unit\Services\Importacion;

use App\Enums\ImportRowEstado;
use App\Services\Importacion\ExcelHeaderResolver;
use App\Services\Importacion\RowValidator;
use App\Services\Importacion\UbicacionResolver;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class RowValidatorTest extends TestCase
{
    private RowValidator $validator;

    private \App\Services\Importacion\HeaderMap $headers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new RowValidator(new UbicacionResolver);
        $this->headers = (new ExcelHeaderResolver)->resolve([
            'fecha documentos', 'Modulo', 'Cliente', 'Mercancia', '#Referencia',
            'Detalle', 'Observación', 'Unidad', 'Contenedor', 'fecha deposito',
            'FECHA DE DESPACHO', 'DESPACHO', 'INVENTARIO',
        ]);
    }

    private function fila(array $sobreescribir = []): array
    {
        $base = ['9/4/2026','Modulo 2 Bloque A','CLIENTE X','VIDRIO','REF-1','4MM','','10','MRKU9517467','2/05/2026','','','10'];
        foreach ($sobreescribir as $idx => $valor) {
            $base[$idx] = $valor;
        }

        return $base;
    }

    #[Test]
    public function fila_valida_pasa(): void
    {
        $r = $this->validator->validar($this->fila(), $this->headers);

        self::assertSame(ImportRowEstado::Importado, $r->estado);
        self::assertSame('MRKU9517467', $r->datosNormalizados['contenedor']);
        self::assertSame(10, $r->datosNormalizados['unidad']);
        self::assertSame(10, $r->datosNormalizados['inventario_fisico']);
    }

    #[Test]
    public function cliente_vacio_es_error(): void
    {
        $r = $this->validator->validar($this->fila([2 => '']), $this->headers);

        self::assertSame(ImportRowEstado::Error, $r->estado);
        self::assertSame(RowValidator::ERR_CLIENTE_NO_RESUELTO, $r->tipoError);
    }

    #[Test]
    public function contenedor_vacio_es_error(): void
    {
        $r = $this->validator->validar($this->fila([8 => '']), $this->headers);

        self::assertSame(ImportRowEstado::Error, $r->estado);
        self::assertSame(RowValidator::ERR_CONTENEDOR_FALTANTE, $r->tipoError);
    }

    #[Test]
    public function fecha_basura_es_error(): void
    {
        $r = $this->validator->validar($this->fila([9 => 'XX']), $this->headers);

        self::assertSame(ImportRowEstado::Error, $r->estado);
        self::assertSame(RowValidator::ERR_FECHA_INVALIDA, $r->tipoError);
    }

    #[Test]
    public function cantidad_no_numerica_es_error(): void
    {
        $r = $this->validator->validar($this->fila([7 => '#']), $this->headers);

        self::assertSame(ImportRowEstado::Error, $r->estado);
        self::assertSame(RowValidator::ERR_CANTIDAD_INVALIDA, $r->tipoError);
    }

    #[Test]
    public function normaliza_contenedor_a_mayusculas_sin_espacios(): void
    {
        $r = $this->validator->validar($this->fila([8 => '  mrku 9517467 ']), $this->headers);

        self::assertSame('MRKU9517467', $r->datosNormalizados['contenedor']);
    }

    #[Test]
    public function detecta_saldo_inconsistente_como_advertencia_no_error(): void
    {
        // unidades=10, despacho=3, inventario=5 ⇒ 10-3=7 ≠ 5
        $r = $this->validator->validar($this->fila([10 => '15/3/2026', 11 => '3', 12 => '5']), $this->headers);

        self::assertSame(ImportRowEstado::Importado, $r->estado);
        $tipos = array_column($r->advertencias, 'tipo');
        self::assertContains(RowValidator::ADV_SALDO_INCONSISTENTE, $tipos);
    }

    #[Test]
    public function detecta_par_despacho_incompleto_como_advertencia(): void
    {
        $r = $this->validator->validar($this->fila([10 => '15/3/2026', 11 => '']), $this->headers);

        $tipos = array_column($r->advertencias, 'tipo');
        self::assertContains(RowValidator::ADV_DESPACHO_INCOMPLETO, $tipos);
    }

    #[Test]
    public function detecta_ubicacion_no_normalizada_como_advertencia(): void
    {
        $r = $this->validator->validar($this->fila([1 => 'Patio Principal']), $this->headers);

        self::assertSame(ImportRowEstado::Importado, $r->estado);
        $tipos = array_column($r->advertencias, 'tipo');
        self::assertContains(RowValidator::ADV_UBICACION_NO_NORMALIZADA, $tipos);
    }
}
