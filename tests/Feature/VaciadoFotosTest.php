<?php

namespace Tests\Feature;

use App\Enums\ContenedorEstado;
use App\Enums\OrdenVaciadoEstado;
use App\Models\Contenedor;
use App\Models\OrdenVaciado;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VaciadoFotosTest extends TestCase
{
    use RefreshDatabase;

    public function test_crear_vaciado_con_varias_fotos_las_guarda(): void
    {
        Storage::fake('public');
        $this->seed(RolesAndPermissionsSeeder::class);
        $supervisor = User::factory()->create();
        $supervisor->assignRole('supervisor');
        $contenedor = Contenedor::create(['numero' => 'C1', 'estado' => ContenedorEstado::EnPatio, 'fecha_ingreso' => now()]);

        $this->actingAs($supervisor)->post(route('vaciado.store'), [
            'contenedor_id' => $contenedor->id,
            'fecha_programada' => now()->toDateString(),
            'fotos' => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.jpg'), UploadedFile::fake()->image('c.jpg')],
        ])->assertRedirect();

        $this->assertSame(3, OrdenVaciado::first()->fotos()->count());
    }

    public function test_editar_vaciado_agrega_fotos_sin_reemplazar(): void
    {
        Storage::fake('public');
        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('administrador');
        $supervisor = User::factory()->create();
        $supervisor->assignRole('supervisor');

        $contenedor = Contenedor::create(['numero' => 'C1', 'estado' => ContenedorEstado::EnVaciado, 'fecha_ingreso' => now()]);
        $orden = OrdenVaciado::create([
            'contenedor_id' => $contenedor->id,
            'supervisor_id' => $supervisor->id,
            'fecha_programada' => now()->toDateString(),
            'estado' => OrdenVaciadoEstado::EnProceso,
        ]);
        // dos fotos iniciales
        $orden->guardarFotos([UploadedFile::fake()->image('x.jpg'), UploadedFile::fake()->image('y.jpg')], 'vaciado/'.$orden->id.'/fotos');
        $this->assertSame(2, $orden->fresh()->fotos()->count());

        // editar agregando una más → se suma
        $this->actingAs($admin)->put(route('vaciado.update', $orden), [
            'fecha_programada' => now()->toDateString(),
            'supervisor_id' => $supervisor->id,
            'notas' => 'editado',
            'fotos' => [UploadedFile::fake()->image('z.jpg')],
        ])->assertRedirect();

        $this->assertSame(3, $orden->fresh()->fotos()->count());
    }
}
