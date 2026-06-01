<?php

namespace Tests\Feature\Edicion;

use App\Enums\ContenedorEstado;
use App\Models\GateEvent;
use PHPUnit\Framework\Attributes\Test;

class EditarGateOutTest extends EdicionTestCase
{
    private function salida(): GateEvent
    {
        return GateEvent::create([
            'contenedor_id' => $this->contenedor(ContenedorEstado::FueraDePatio)->id,
            'tipo' => 'gate_out',
            'usuario_id' => $this->usuarioConRol('portero')->id,
            'hora' => now(),
            'estado_fisico' => 'Bueno',
            'notas' => 'Original',
        ]);
    }

    #[Test]
    public function administrador_edita_una_salida_y_queda_auditado(): void
    {
        $salida = $this->salida();

        $this->actingAs($this->admin())
            ->put(route('gate-out.update', $salida), [
                'hora' => now()->subDays(3)->format('Y-m-d\TH:i'),
                'estado_fisico' => 'Limpio',
                'notas' => 'Corregido',
            ])
            ->assertSessionHasNoErrors();

        $salida->refresh();
        self::assertSame('Limpio', $salida->estado_fisico);
        $this->assertDatabaseHas('cambios_auditoria', [
            'auditable_type' => GateEvent::class,
            'auditable_id' => $salida->id,
        ]);
    }

    #[Test]
    public function rechaza_edicion_invalida(): void
    {
        $salida = $this->salida();

        $this->actingAs($this->admin())
            ->put(route('gate-out.update', $salida), [
                'hora' => '',
            ])
            ->assertSessionHasErrors('hora');

        $this->assertDatabaseCount('cambios_auditoria', 0);
    }

    #[Test]
    public function un_rol_no_autorizado_no_puede_editar(): void
    {
        $salida = $this->salida();

        $this->actingAs($this->usuarioConRol('portero'))
            ->get(route('gate-out.editar', $salida))
            ->assertForbidden();
    }
}
