<?php

namespace Tests\Unit\Services\Importacion;

use App\Models\User;
use App\Services\Importacion\ClienteResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ClienteResolverTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function modo_memoria_no_persiste_ni_consulta_clientes_inexistentes(): void
    {
        $resolver = new ClienteResolver(modoCacheMemoria: true);

        $a = $resolver->resolver('CLIENTE NUEVO SAS');
        $b = $resolver->resolver('CLIENTE NUEVO SAS');

        self::assertFalse($a->exists);
        self::assertSame($a, $b, 'Mismo nombre debe devolver instancia cacheada');
        self::assertSame(0, User::query()->count());
        self::assertTrue($resolver->fueAutocreado('CLIENTE NUEVO SAS'));
        self::assertSame(1, $resolver->totalAutocreados());
    }

    #[Test]
    public function modo_memoria_reusa_cliente_existente_de_bd(): void
    {
        Role::findOrCreate('cliente', 'web');
        $existente = User::factory()->create(['name' => 'CLIENTE REAL SAS']);

        $resolver = new ClienteResolver(modoCacheMemoria: true);
        $r = $resolver->resolver('CLIENTE REAL SAS');

        self::assertSame($existente->id, $r->id);
        self::assertFalse($resolver->fueAutocreado('CLIENTE REAL SAS'));
    }
}
