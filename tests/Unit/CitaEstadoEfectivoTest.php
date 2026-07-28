<?php

namespace Tests\Unit;

use App\Enums\CitaEstado;
use App\Models\Cita;
use App\Support\Normalizador;
use Tests\TestCase;

/**
 * Lógica pura del estado efectivo y de la normalización: no toca base de datos,
 * pero sí necesita el contenedor de Laravel (los casts de Eloquent lo usan), por
 * eso extiende Tests\TestCase y no PHPUnit\Framework\TestCase.
 */
class CitaEstadoEfectivoTest extends TestCase
{
    private function citaCon(CitaEstado $estado, string $fecha): Cita
    {
        $cita = new Cita();
        $cita->estado = $estado;
        $cita->fecha_esperada = $fecha;

        return $cita;
    }

    public function test_programada_con_fecha_pasada_es_vencida(): void
    {
        $cita = $this->citaCon(CitaEstado::Programada, now()->subDays(3)->toDateString());

        $this->assertSame(CitaEstado::Vencida, $cita->estadoEfectivo());
    }

    public function test_programada_de_hoy_sigue_programada(): void
    {
        $cita = $this->citaCon(CitaEstado::Programada, now()->toDateString());

        $this->assertSame(CitaEstado::Programada, $cita->estadoEfectivo());
    }

    public function test_programada_a_futuro_sigue_programada(): void
    {
        $cita = $this->citaCon(CitaEstado::Programada, now()->addDays(2)->toDateString());

        $this->assertSame(CitaEstado::Programada, $cita->estadoEfectivo());
    }

    public function test_atendida_con_fecha_pasada_no_se_vence(): void
    {
        $cita = $this->citaCon(CitaEstado::Atendida, now()->subDays(10)->toDateString());

        $this->assertSame(CitaEstado::Atendida, $cita->estadoEfectivo());
    }

    public function test_cancelada_con_fecha_pasada_no_se_vence(): void
    {
        $cita = $this->citaCon(CitaEstado::Cancelada, now()->subDays(10)->toDateString());

        $this->assertSame(CitaEstado::Cancelada, $cita->estadoEfectivo());
    }

    public function test_solo_una_cita_programada_es_editable(): void
    {
        $this->assertTrue($this->citaCon(CitaEstado::Programada, now()->toDateString())->puedeEditarse());
        // Una vencida sigue siendo editable: cambiarle la fecha es reprogramarla.
        $this->assertTrue($this->citaCon(CitaEstado::Programada, now()->subDay()->toDateString())->puedeEditarse());
        $this->assertFalse($this->citaCon(CitaEstado::Atendida, now()->toDateString())->puedeEditarse());
        $this->assertFalse($this->citaCon(CitaEstado::Cancelada, now()->toDateString())->puedeEditarse());
    }

    public function test_vencida_nunca_es_un_estado_persistible(): void
    {
        $this->assertNotContains(CitaEstado::Vencida->value, CitaEstado::persistibles());
        $this->assertContains(CitaEstado::Programada->value, CitaEstado::persistibles());
        $this->assertContains(CitaEstado::Atendida->value, CitaEstado::persistibles());
        $this->assertContains(CitaEstado::Cancelada->value, CitaEstado::persistibles());
    }

    /**
     * Todas estas formas de escribir la misma placa deben normalizar igual: es lo
     * que permite que el portero la encuentre en la puerta.
     */
    public function test_la_normalizacion_unifica_los_formatos_digitados(): void
    {
        foreach (['ABC123', 'abc123', 'ABC-123', 'abc 123', ' A.B.C-1 2 3 '] as $entrada) {
            $this->assertSame('ABC123', Normalizador::identificador($entrada), "Falló con `{$entrada}`");
        }
    }

    public function test_la_normalizacion_devuelve_null_si_no_queda_nada(): void
    {
        $this->assertNull(Normalizador::identificador(null));
        $this->assertNull(Normalizador::identificador(''));
        $this->assertNull(Normalizador::identificador('---'));
    }
}
