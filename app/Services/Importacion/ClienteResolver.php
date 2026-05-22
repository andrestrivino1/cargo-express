<?php

namespace App\Services\Importacion;

use App\Models\ImportBatch;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Resuelve el User cliente a partir del nombre que aparece en la hoja.
 *
 * - Modo `validar` (caché en memoria): no toca BD; mantiene un Set propio
 *   para evitar reportar el mismo "cliente no resuelto" varias veces.
 * - Modo `importar`: busca por nombre; si no existe, lo auto-crea con email
 *   placeholder y password genérica (FR-024 a FR-026). US2 utiliza este modo.
 */
final class ClienteResolver
{
    /** @var Collection<string, User> */
    private Collection $cache;

    /** @var Collection<string, true> */
    private Collection $autocreados;

    public function __construct(private readonly bool $modoCacheMemoria)
    {
        $this->cache = collect();
        $this->autocreados = collect();
    }

    public function resolver(string $nombreCliente, ?ImportBatch $batch = null): User
    {
        $clave = $this->clave($nombreCliente);

        $cached = $this->cache->get($clave);
        if ($cached !== null) {
            return $cached;
        }

        $existente = User::query()->where('name', $nombreCliente)->first();
        if ($existente !== null) {
            $this->cache->put($clave, $existente);

            return $existente;
        }

        if ($this->modoCacheMemoria) {
            $sintetico = new User([
                'name' => $nombreCliente,
                'email' => $this->emailPlaceholder($nombreCliente),
                'requiere_cambio_password' => true,
                'email_placeholder' => true,
            ]);
            $sintetico->exists = false;
            $this->cache->put($clave, $sintetico);
            $this->autocreados->put($clave, true);

            return $sintetico;
        }

        $creado = User::create([
            'name' => $nombreCliente,
            'email' => $this->emailPlaceholder($nombreCliente),
            'password' => Hash::make(config('importacion.password_generica')),
            'requiere_cambio_password' => true,
            'email_placeholder' => true,
            'import_batch_id_origen' => $batch?->getKey(),
        ]);
        $creado->assignRole('cliente');

        $this->cache->put($clave, $creado);
        $this->autocreados->put($clave, true);

        return $creado;
    }

    public function fueAutocreado(string $nombreCliente): bool
    {
        return $this->autocreados->has($this->clave($nombreCliente));
    }

    public function totalAutocreados(): int
    {
        return $this->autocreados->count();
    }

    /** @return array<int, string> */
    public function nombresAutocreados(): array
    {
        return $this->autocreados->keys()
            ->map(fn ($clave) => $this->cache->get($clave)?->name ?? $clave)
            ->all();
    }

    private function clave(string $nombre): string
    {
        return mb_strtolower(trim($nombre));
    }

    private function emailPlaceholder(string $nombre): string
    {
        return Str::slug($nombre).'@'.config('importacion.dominio_placeholder');
    }
}
