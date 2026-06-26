<?php

namespace App\Services;

use App\Enums\DocumentoCategoria;
use App\Enums\OrdenCargueEstado;
use App\Models\OrdenCargue;
use App\Models\Referencia;
use App\Models\Tarja;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class SalidaMercanciaService
{
    public function __construct(
        private readonly MovimientoInventarioService $movimientos,
        private readonly ConsecutivoService $consecutivos,
    ) {}

    /**
     * Registra una salida de mercancía consolidada: descuenta inventario de forma
     * atómica, registra los movimientos de salida, asigna el consecutivo ODC y
     * guarda las evidencias fotográficas (mercancía y conductor).
     *
     * @param  array<string, mixed>  $data
     * @param  array{mercancia: UploadedFile, conductor: UploadedFile}  $fotos
     *
     * @throws ValidationException si el saldo disponible es insuficiente.
     */
    public function registrar(array $data, array $fotos, User $despachador): Tarja
    {
        return DB::transaction(function () use ($data, $fotos, $despachador) {
            // Guardar/actualizar el NIT en el cliente para que el ODC lo muestre y
            // quede precargado en futuras salidas.
            if (! empty($data['nit'])) {
                User::whereKey($data['cliente_id'])->update(['nit' => $data['nit']]);
            }

            $ordenCargue = OrdenCargue::create([
                'cliente_id' => $data['cliente_id'],
                'despachador_id' => $despachador->id,
                'fecha_despacho' => $data['fecha_salida'],
                'estado' => OrdenCargueEstado::Pendiente,
                'notas' => $data['observaciones'] ?? null,
            ]);

            $tarja = $ordenCargue->tarjas()->create([
                'despachador_id' => $despachador->id,
                'fecha_entrega' => $data['fecha_salida'],
                'observaciones' => $data['observaciones'] ?? null,
                'vehiculo' => $data['placa_vehiculo'],
                'conductor' => $data['conductor'],
                'conductor_cedula' => $data['conductor_cedula'] ?? null,
                'transportador' => $data['transportador'],
                'destino' => $data['destino'],
                'consecutivo_odc' => $this->consecutivos->siguiente('odc'),
            ]);

            foreach ($data['detalles'] as $detalle) {
                $cantidad = (int) $detalle['cantidad'];

                // Bloqueo de fila para evitar saldos negativos en concurrencia.
                $referencia = Referencia::query()
                    ->whereKey($detalle['referencia_id'])
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($cantidad > $referencia->cantidad_actual) {
                    throw ValidationException::withMessages([
                        'detalles' => "Saldo insuficiente para la referencia {$referencia->codigo}. ".
                            "Disponible: {$referencia->cantidad_actual}, solicitado: {$cantidad}.",
                    ]);
                }

                $referencia->decrement('cantidad_actual', $cantidad);
                $referencia->refresh();

                if ((int) $referencia->cantidad_actual === 0) {
                    $referencia->update(['fecha_salida' => now()]);
                }

                $tarja->detalles()->create([
                    'referencia_id' => $referencia->id,
                    'cantidad_entregada' => $cantidad,
                    'ubicacion_origen_id' => $referencia->ubicacion_patio_id,
                ]);

                $this->movimientos->registrarSalida(
                    $referencia,
                    $cantidad,
                    $despachador,
                    $tarja,
                    $data['observaciones'] ?? null,
                );
            }

            $carpeta = "salidas/{$tarja->id}";
            $tarja->guardarArchivo($fotos['mercancia'], $carpeta, 'foto', DocumentoCategoria::FotoMercancia->value);
            $tarja->guardarArchivo($fotos['conductor'], $carpeta, 'foto', DocumentoCategoria::FotoConductor->value);

            $ordenCargue->update(['estado' => OrdenCargueEstado::Completada]);

            return $tarja;
        });
    }

    /**
     * Reemplaza una evidencia (foto_mercancia / foto_conductor) por una nueva,
     * borrando la anterior del disco. Conserva la categoría.
     */
    public function reemplazarFoto(Tarja $tarja, string $categoria, UploadedFile $archivo): void
    {
        $anterior = $tarja->photos()->where('categoria', $categoria)->first();
        if ($anterior) {
            Storage::disk('public')->delete($anterior->ruta);
            $anterior->delete();
        }

        $tarja->guardarArchivo($archivo, "salidas/{$tarja->id}", 'foto', $categoria);
    }

    /**
     * Lista las salidas (tarjas con consecutivo ODC) paginadas.
     */
    public function listar(array $filtros)
    {
        $query = Tarja::query()
            ->whereNotNull('consecutivo_odc')
            ->with(['ordenCargue.cliente', 'despachador']);

        if (! empty($filtros['cliente_id'])) {
            $query->whereHas('ordenCargue', fn ($q) => $q->where('cliente_id', $filtros['cliente_id']));
        }

        return $query->orderByDesc('consecutivo_odc')->paginate(15);
    }
}
