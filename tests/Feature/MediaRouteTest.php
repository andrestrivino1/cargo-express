<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaRouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_sirve_archivo_existente_y_404_si_no_existe(): void
    {
        Storage::fake('public');
        $u = User::factory()->create();
        Storage::disk('public')->put('salidas/9/foto.jpg', 'x');
        $this->actingAs($u)->get('/media/salidas/9/foto.jpg')->assertOk();
        $this->actingAs($u)->get('/media/salidas/9/missing.jpg')->assertNotFound();
    }
}
