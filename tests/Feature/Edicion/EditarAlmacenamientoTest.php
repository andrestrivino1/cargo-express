<?php

namespace Tests\Feature\Edicion;

use App\Models\Referencia;
use PHPUnit\Framework\Attributes\Test;

class EditarAlmacenamientoTest extends EdicionTestCase
{
    #[Test]
    public function administrador_edita_una_referencia_sin_alterar_cantidades(): void
    {
        $referencia = $this->referencia();
        $cantidadOriginal = $referencia->cantidad_actual;
        $nuevaUbicacion = $this->ubicacion('B', '02');

        $this->actingAs($this->admin())
            ->put(route('inventario.update', $referencia), [
                'codigo' => 'REF-CORREGIDA',
                'descripcion' => 'Descripción corregida',
                'unidad_medida' => 'cajas',
                'ubicacion_patio_id' => $nuevaUbicacion->id,
                'fecha_ingreso' => now()->subDays(5)->format('Y-m-d\TH:i'),
            ])
            ->assertRedirect(route('inventario.index'))
            ->assertSessionHasNoErrors();

        $referencia->refresh();
        self::assertSame('REF-CORREGIDA', $referencia->codigo);
        self::assertSame($nuevaUbicacion->id, $referencia->ubicacion_patio_id);
        // Las cantidades de inventario no cambian (FR-004)
        self::assertSame($cantidadOriginal, $referencia->cantidad_actual);
        $this->assertDatabaseHas('cambios_auditoria', [
            'auditable_type' => Referencia::class,
            'auditable_id' => $referencia->id,
        ]);
    }

    #[Test]
    public function rechaza_edicion_invalida(): void
    {
        $referencia = $this->referencia();

        $this->actingAs($this->admin())
            ->put(route('inventario.update', $referencia), [
                'codigo' => '', // requerido
            ])
            ->assertSessionHasErrors('codigo');

        $this->assertDatabaseCount('cambios_auditoria', 0);
    }

    #[Test]
    public function un_rol_no_autorizado_no_puede_editar(): void
    {
        $referencia = $this->referencia();

        // supervisor puede ver inventario pero no editar
        $this->actingAs($this->usuarioConRol('supervisor'))
            ->get(route('inventario.editar', $referencia))
            ->assertForbidden();
    }
}
