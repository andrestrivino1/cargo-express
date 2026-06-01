<?php

namespace Tests\Feature\Edicion;

use App\Models\GateEvent;
use PHPUnit\Framework\Attributes\Test;

class EditarGateInTest extends EdicionTestCase
{
    private function ingreso(): GateEvent
    {
        return GateEvent::create([
            'contenedor_id' => $this->contenedor()->id,
            'tipo' => 'gate_in',
            'usuario_id' => $this->usuarioConRol('portero')->id,
            'hora' => now(),
            'estado_fisico' => 'Bueno',
            'notas' => 'Original',
        ]);
    }

    #[Test]
    public function administrador_edita_un_ingreso_y_queda_auditado(): void
    {
        $ingreso = $this->ingreso();

        $this->actingAs($this->admin())
            ->put(route('gate-in.update', $ingreso), [
                'hora' => now()->subDays(2)->format('Y-m-d\TH:i'),
                'estado_fisico' => 'Con abolladura',
                'notas' => 'Corregido',
            ])
            ->assertRedirect(route('gate-in.index'))
            ->assertSessionHasNoErrors();

        $ingreso->refresh();
        self::assertSame('Con abolladura', $ingreso->estado_fisico);
        self::assertSame('Corregido', $ingreso->notas);
        $this->assertDatabaseHas('cambios_auditoria', [
            'auditable_type' => GateEvent::class,
            'auditable_id' => $ingreso->id,
        ]);
    }

    #[Test]
    public function rechaza_edicion_invalida(): void
    {
        $ingreso = $this->ingreso();

        $this->actingAs($this->admin())
            ->put(route('gate-in.update', $ingreso), [
                'hora' => '',
            ])
            ->assertSessionHasErrors('hora');

        $this->assertDatabaseCount('cambios_auditoria', 0);
    }

    #[Test]
    public function un_rol_no_autorizado_no_puede_editar(): void
    {
        $ingreso = $this->ingreso();

        $this->actingAs($this->usuarioConRol('portero'))
            ->get(route('gate-in.editar', $ingreso))
            ->assertForbidden();
    }
}
