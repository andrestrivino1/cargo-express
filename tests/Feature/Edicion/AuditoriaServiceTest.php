<?php

namespace Tests\Feature\Edicion;

use App\Enums\SolicitudEstado;
use App\Models\Solicitud;
use App\Models\User;
use App\Services\AuditoriaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuditoriaServiceTest extends TestCase
{
    use RefreshDatabase;

    private function solicitud(): Solicitud
    {
        $cliente = User::factory()->create();

        return Solicitud::create([
            'cliente_id' => $cliente->id,
            'numero_contenedor' => 'AUD123',
            'estado' => SolicitudEstado::Pendiente,
            'fecha_solicitud' => now(),
        ]);
    }

    #[Test]
    public function registra_una_entrada_con_diff_de_los_campos_modificados(): void
    {
        $usuario = User::factory()->create();
        $solicitud = $this->solicitud();

        $solicitud->numero_contenedor = 'NUEVO999';
        $solicitud->naviera = 'Maersk';

        $entrada = app(AuditoriaService::class)->registrarCambios($solicitud, $usuario);
        $solicitud->save();

        self::assertNotNull($entrada);
        $this->assertDatabaseCount('cambios_auditoria', 1);
        self::assertSame('AUD123', $entrada->cambios['numero_contenedor']['anterior']);
        self::assertSame('NUEVO999', $entrada->cambios['numero_contenedor']['nuevo']);
        self::assertArrayHasKey('naviera', $entrada->cambios);
        self::assertSame($usuario->id, $entrada->usuario_id);
    }

    #[Test]
    public function no_registra_entrada_cuando_no_hay_cambios(): void
    {
        $usuario = User::factory()->create();
        $solicitud = $this->solicitud();

        $entrada = app(AuditoriaService::class)->registrarCambios($solicitud, $usuario);

        self::assertNull($entrada);
        $this->assertDatabaseCount('cambios_auditoria', 0);
    }

    #[Test]
    public function el_historial_del_registro_lista_las_entradas_mas_recientes_primero(): void
    {
        $usuario = User::factory()->create();
        $solicitud = $this->solicitud();

        $solicitud->naviera = 'Primera';
        app(AuditoriaService::class)->registrarCambios($solicitud, $usuario);
        $solicitud->save();

        $solicitud->naviera = 'Segunda';
        app(AuditoriaService::class)->registrarCambios($solicitud, $usuario);
        $solicitud->save();

        $historial = $solicitud->cambiosAuditoria()->get();

        self::assertCount(2, $historial);
        self::assertSame('Segunda', $historial->first()->cambios['naviera']['nuevo']);
    }
}
