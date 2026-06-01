<?php

namespace Tests\Feature\Edicion;

use App\Models\Transferencia;
use PHPUnit\Framework\Attributes\Test;

class EditarTransferenciaTest extends EdicionTestCase
{
    private function transferencia(): Transferencia
    {
        $referencia = $this->referencia();

        return Transferencia::create([
            'tipo' => 'entre_modulos',
            'usuario_id' => $this->admin()->id,
            'referencia_origen_id' => $referencia->id,
            'ubicacion_origen_id' => $this->ubicacion('C', '03')->id,
            'ubicacion_destino_id' => $this->ubicacion('D', '04')->id,
            'cantidad' => 10,
            'motivo' => 'Original',
            'autorizacion_cliente' => 'AUT-001',
        ]);
    }

    #[Test]
    public function administrador_edita_una_transferencia_sin_alterar_cantidad(): void
    {
        $transferencia = $this->transferencia();
        $cantidadOriginal = $transferencia->cantidad;

        $this->actingAs($this->admin())
            ->put(route('transferencias.update', $transferencia), [
                'motivo' => 'Motivo corregido',
                'autorizacion_cliente' => 'AUT-999',
            ])
            ->assertRedirect(route('transferencias.show', $transferencia))
            ->assertSessionHasNoErrors();

        $transferencia->refresh();
        self::assertSame('Motivo corregido', $transferencia->motivo);
        // La cantidad transferida no cambia (FR-004)
        self::assertSame($cantidadOriginal, $transferencia->cantidad);
        $this->assertDatabaseHas('cambios_auditoria', [
            'auditable_type' => Transferencia::class,
            'auditable_id' => $transferencia->id,
        ]);
    }

    #[Test]
    public function un_rol_no_autorizado_no_puede_editar(): void
    {
        $transferencia = $this->transferencia();

        // operador puede ubicar inventario pero no editar transferencias
        $this->actingAs($this->usuarioConRol('operador'))
            ->get(route('transferencias.editar', $transferencia))
            ->assertForbidden();
    }
}
