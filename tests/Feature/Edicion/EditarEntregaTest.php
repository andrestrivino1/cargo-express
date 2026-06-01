<?php

namespace Tests\Feature\Edicion;

use App\Enums\OrdenCargueEstado;
use App\Models\OrdenCargue;
use PHPUnit\Framework\Attributes\Test;

class EditarEntregaTest extends EdicionTestCase
{
    private function entrega(): OrdenCargue
    {
        return OrdenCargue::create([
            'cliente_id' => $this->usuarioConRol('cliente')->id,
            'fecha_despacho' => now()->startOfDay(),
            'estado' => OrdenCargueEstado::Completada,
            'notas' => 'Original',
        ]);
    }

    #[Test]
    public function administrador_edita_una_entrega_y_queda_auditado(): void
    {
        $entrega = $this->entrega();
        $estadoOriginal = $entrega->estado;
        $nuevoCliente = $this->usuarioConRol('cliente');

        $this->actingAs($this->admin())
            ->put(route('entregas.update', $entrega), [
                'cliente_id' => $nuevoCliente->id,
                'fecha_despacho' => now()->subDays(7)->format('Y-m-d'),
                'notas' => 'Corregido',
            ])
            ->assertRedirect(route('entregas.show', $entrega))
            ->assertSessionHasNoErrors();

        $entrega->refresh();
        self::assertSame($nuevoCliente->id, $entrega->cliente_id);
        self::assertSame('Corregido', $entrega->notas);
        self::assertSame($estadoOriginal, $entrega->estado); // estado intacto (FR-010)
        $this->assertDatabaseHas('cambios_auditoria', [
            'auditable_type' => OrdenCargue::class,
            'auditable_id' => $entrega->id,
        ]);
    }

    #[Test]
    public function rechaza_edicion_invalida(): void
    {
        $entrega = $this->entrega();

        $this->actingAs($this->admin())
            ->put(route('entregas.update', $entrega), [
                'cliente_id' => $entrega->cliente_id,
                'fecha_despacho' => '',
            ])
            ->assertSessionHasErrors('fecha_despacho');

        $this->assertDatabaseCount('cambios_auditoria', 0);
    }

    #[Test]
    public function un_rol_no_autorizado_no_puede_editar(): void
    {
        $entrega = $this->entrega();

        // despachador puede ver entregas pero no editar
        $this->actingAs($this->usuarioConRol('despachador'))
            ->get(route('entregas.editar', $entrega))
            ->assertForbidden();
    }
}
