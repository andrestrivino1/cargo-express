<?php

namespace Tests\Feature\Edicion;

use App\Enums\OrdenVaciadoEstado;
use App\Models\OrdenVaciado;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;

class EditarVaciadoTest extends EdicionTestCase
{
    private function ordenFinalizada(): OrdenVaciado
    {
        $supervisor = $this->usuarioConRol('supervisor');

        return OrdenVaciado::create([
            'contenedor_id' => $this->contenedor()->id,
            'supervisor_id' => $supervisor->id,
            'fecha_programada' => now()->startOfDay(),
            'estado' => OrdenVaciadoEstado::Completada,
            'notas' => 'Original',
        ]);
    }

    #[Test]
    public function administrador_edita_un_vaciado_finalizado_y_queda_auditado(): void
    {
        $orden = $this->ordenFinalizada();
        $estadoOriginal = $orden->estado;
        $nuevoSupervisor = $this->usuarioConRol('supervisor');

        $this->actingAs($this->admin())
            ->put(route('vaciado.update', $orden), [
                'fecha_programada' => now()->subDays(10)->format('Y-m-d'),
                'supervisor_id' => $nuevoSupervisor->id,
                'notas' => 'Corregido',
            ])
            ->assertRedirect(route('vaciado.show', $orden))
            ->assertSessionHasNoErrors();

        $orden->refresh();
        self::assertSame('Corregido', $orden->notas);
        self::assertSame($nuevoSupervisor->id, $orden->supervisor_id);
        self::assertSame($estadoOriginal, $orden->estado); // estado intacto (FR-010)
        $this->assertDatabaseHas('cambios_auditoria', [
            'auditable_type' => OrdenVaciado::class,
            'auditable_id' => $orden->id,
        ]);
    }

    #[Test]
    public function rechaza_edicion_invalida(): void
    {
        $orden = $this->ordenFinalizada();

        $this->actingAs($this->admin())
            ->put(route('vaciado.update', $orden), [
                'fecha_programada' => '',
                'supervisor_id' => $orden->supervisor_id,
            ])
            ->assertSessionHasErrors('fecha_programada');

        $this->assertDatabaseCount('cambios_auditoria', 0);
    }

    #[Test]
    public function un_rol_no_autorizado_no_puede_editar(): void
    {
        $orden = $this->ordenFinalizada();

        // supervisor puede ver vaciado pero no editar
        $this->actingAs($this->usuarioConRol('supervisor'))
            ->get(route('vaciado.editar', $orden))
            ->assertForbidden();
    }
}
