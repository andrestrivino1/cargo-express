<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Matriz completa rol × módulo (contracts/matriz-roles.md).
 *
 * Cubre SC-006: "0 accesos exitosos a módulos fuera de su alcance".
 */
class MatrizRolesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function con(string $rol): User
    {
        $user = User::factory()->create();
        $user->assignRole($rol);

        return $user;
    }

    /**
     * Cada fila: [rol, ruta, ¿debe tener acceso?]
     *
     * @return array<string, array{0: string, 1: string, 2: bool}>
     */
    public static function matriz(): array
    {
        return [
            // --- citas ---
            'citas accede a Citas' => ['citas', 'citas.index', true],
            'citas consulta Ingreso' => ['citas', 'ingreso.index', true],
            'citas NO crea Ingreso' => ['citas', 'ingreso.create', false],
            'citas NO accede a Portero' => ['citas', 'porteria.index', false],
            'citas NO accede a Salida' => ['citas', 'salida.index', false],
            'citas NO accede a Vaciado' => ['citas', 'vaciado.index', false],
            'citas NO accede a Almacenamiento' => ['citas', 'inventario.index', false],
            'citas NO accede a Reportes' => ['citas', 'reportes.index', false],
            'citas NO accede a Administración' => ['citas', 'admin.usuarios.index', false],

            // --- operaciones ---
            'operaciones accede a Ingreso' => ['operaciones', 'ingreso.index', true],
            'operaciones crea Ingreso' => ['operaciones', 'ingreso.create', true],
            'operaciones accede a Salida' => ['operaciones', 'salida.index', true],
            'operaciones NO accede a Citas' => ['operaciones', 'citas.index', false],
            'operaciones NO accede a Portero' => ['operaciones', 'porteria.index', false],
            'operaciones NO accede a Vaciado' => ['operaciones', 'vaciado.index', false],
            'operaciones NO accede a Administración' => ['operaciones', 'admin.usuarios.index', false],

            // --- portero ---
            'portero accede a Portero' => ['portero', 'porteria.index', true],
            'portero NO accede a Ingreso' => ['portero', 'ingreso.index', false],
            'portero NO accede a Salida' => ['portero', 'salida.index', false],
            'portero NO accede a Citas' => ['portero', 'citas.index', false],
            'portero NO accede a Vaciado' => ['portero', 'vaciado.index', false],
            'portero NO accede a Almacenamiento' => ['portero', 'inventario.index', false],

            // --- supervisor ---
            'supervisor accede a Vaciado' => ['supervisor', 'vaciado.index', true],
            'supervisor accede a Almacenamiento' => ['supervisor', 'inventario.index', true],
            'supervisor ubica mercancía' => ['supervisor', 'inventario.ubicar', true],
            'supervisor accede a Transferencias' => ['supervisor', 'transferencias.index', true],
            'supervisor accede a Reportes' => ['supervisor', 'reportes.index', true],
            'supervisor NO accede a Ingreso' => ['supervisor', 'ingreso.index', false],
            'supervisor NO accede a Salida' => ['supervisor', 'salida.index', false],
            'supervisor NO accede a Citas' => ['supervisor', 'citas.index', false],
            'supervisor NO accede a Portero' => ['supervisor', 'porteria.index', false],

            // --- cliente ---
            'cliente accede a su Almacenamiento' => ['cliente', 'inventario.index', true],
            'cliente NO accede a Ingreso' => ['cliente', 'ingreso.index', false],
            'cliente NO accede a Salida' => ['cliente', 'salida.index', false],
            'cliente NO accede a Citas' => ['cliente', 'citas.index', false],
            'cliente NO accede a Portero' => ['cliente', 'porteria.index', false],
            'cliente NO accede a Vaciado' => ['cliente', 'vaciado.index', false],
            'cliente NO accede a Reportes' => ['cliente', 'reportes.index', false],
            'cliente NO ubica mercancía' => ['cliente', 'inventario.ubicar', false],

            // --- administrador ---
            'administrador accede a Citas' => ['administrador', 'citas.index', true],
            'administrador accede a Portero' => ['administrador', 'porteria.index', true],
            'administrador accede a Ingreso' => ['administrador', 'ingreso.index', true],
            'administrador accede a Salida' => ['administrador', 'salida.index', true],
            'administrador accede a Vaciado' => ['administrador', 'vaciado.index', true],
            'administrador accede a Almacenamiento' => ['administrador', 'inventario.index', true],
            'administrador accede a Reportes' => ['administrador', 'reportes.index', true],
            'administrador accede a Administración' => ['administrador', 'admin.usuarios.index', true],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('matriz')]
    public function test_matriz_rol_modulo(string $rol, string $ruta, bool $permitido): void
    {
        $respuesta = $this->actingAs($this->con($rol))->get(route($ruta));

        if ($permitido) {
            $respuesta->assertOk();
        } else {
            $this->assertContains(
                $respuesta->status(),
                [403, 404],
                "El rol `{$rol}` NO debería alcanzar `{$ruta}`, pero respondió {$respuesta->status()}."
            );
        }
    }
}
