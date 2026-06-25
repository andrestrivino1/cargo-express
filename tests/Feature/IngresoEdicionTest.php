<?php

namespace Tests\Feature;

use App\Models\Ingreso;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IngresoEdicionTest extends TestCase
{
    use RefreshDatabase;

    public function test_editar_ingreso_confirma_bl_y_baja_la_bandera(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('administrador');
        $cliente = User::factory()->create();

        // Ingreso como lo crea la importación: BL provisional (= contenedor), por confirmar
        $ingreso = Ingreso::create([
            'bl' => 'MEDU1234567',
            'bl_por_confirmar' => true,
            'cliente_id' => $cliente->id,
            'fecha_ingreso' => now()->subDays(5)->toDateString(),
        ]);

        $response = $this->actingAs($admin)->put(route('ingreso.update', $ingreso), [
            'bl' => 'BL-REAL-001',
            'cliente_id' => $cliente->id,
            'fecha_ingreso' => now()->subDays(5)->toDateString(),
        ]);

        $response->assertRedirect(route('ingreso.show', $ingreso));
        $ingreso->refresh();
        $this->assertSame('BL-REAL-001', $ingreso->bl);
        $this->assertFalse($ingreso->bl_por_confirmar);
    }

    public function test_fecha_futura_es_rechazada_al_editar(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('administrador');
        $cliente = User::factory()->create();
        $ingreso = Ingreso::create([
            'bl' => 'X', 'bl_por_confirmar' => true, 'cliente_id' => $cliente->id, 'fecha_ingreso' => now()->toDateString(),
        ]);

        $response = $this->actingAs($admin)->put(route('ingreso.update', $ingreso), [
            'bl' => 'BL-1', 'cliente_id' => $cliente->id, 'fecha_ingreso' => now()->addDays(2)->toDateString(),
        ]);

        $response->assertSessionHasErrors('fecha_ingreso');
        $this->assertTrue($ingreso->fresh()->bl_por_confirmar);
    }
}
