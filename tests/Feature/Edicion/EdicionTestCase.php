<?php

namespace Tests\Feature\Edicion;

use App\Enums\ContenedorEstado;
use App\Enums\OrdenServicioEstado;
use App\Enums\SolicitudEstado;
use App\Models\Contenedor;
use App\Models\OrdenServicio;
use App\Models\Referencia;
use App\Models\Solicitud;
use App\Models\UbicacionPatio;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class EdicionTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    protected function usuarioConRol(string $rol): User
    {
        $user = User::factory()->create();
        $user->assignRole($rol);

        return $user;
    }

    protected function admin(): User
    {
        return $this->usuarioConRol('administrador');
    }

    protected function contenedor(ContenedorEstado $estado = ContenedorEstado::EnPatio): Contenedor
    {
        $cliente = $this->usuarioConRol('cliente');
        $coordinador = $this->usuarioConRol('coordinador');

        $solicitud = Solicitud::create([
            'cliente_id' => $cliente->id,
            'numero_contenedor' => 'EDIT' . $cliente->id,
            'estado' => SolicitudEstado::Asignada,
            'fecha_solicitud' => now()->startOfDay(),
        ]);

        $ordenServicio = OrdenServicio::create([
            'solicitud_id' => $solicitud->id,
            'coordinador_id' => $coordinador->id,
            'vehiculo' => 'ABC123',
            'conductor' => 'Juan Perez',
            'cita_puerto' => now(),
            'estado' => OrdenServicioEstado::Completada,
        ]);

        return Contenedor::create([
            'orden_servicio_id' => $ordenServicio->id,
            'numero' => $solicitud->numero_contenedor,
            'estado' => $estado,
            'fecha_ingreso' => now(),
        ]);
    }

    protected function ubicacion(string $modulo = 'A', string $posicion = '01'): UbicacionPatio
    {
        return UbicacionPatio::create([
            'modulo' => $modulo,
            'posicion' => $posicion,
            'activa' => true,
        ]);
    }

    protected function referencia(?Contenedor $contenedor = null): Referencia
    {
        $contenedor ??= $this->contenedor();

        return Referencia::create([
            'contenedor_id' => $contenedor->id,
            'cliente_id' => $contenedor->ordenServicio->solicitud->cliente_id,
            'codigo' => 'REF-001',
            'descripcion' => 'Producto de prueba',
            'cantidad_inicial' => 100,
            'cantidad_actual' => 100,
            'unidad_medida' => 'unidades',
            'ubicacion_patio_id' => $this->ubicacion()->id,
            'fecha_ingreso' => now(),
        ]);
    }
}
