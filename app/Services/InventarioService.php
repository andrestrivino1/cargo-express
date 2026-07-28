<?php

namespace App\Services;

use App\Exports\InventarioExport;
use App\Models\Referencia;
use App\Models\UbicacionPatio;
use App\Models\User;
use App\Notifications\UbicacionAsignadaNotification;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class InventarioService
{
    public function asignarUbicacion(Referencia $ref, UbicacionPatio $ubicacion): void
    {
        $ref->update(['ubicacion_patio_id' => $ubicacion->id]);

        if ($ref->cliente) {
            $ref->cliente->notify(new UbicacionAsignadaNotification($ref, $ubicacion));
        }
    }

    /**
     * Un usuario con rol `cliente` solo puede ver su propia mercancía: se fuerza
     * el filtro a su id, descartando cualquier `cliente_id` que venga del
     * request.
     *
     * Punto ÚNICO de aplicación del alcance: lo usan la consulta, la exportación
     * a Excel y la exportación a PDF. Si se aplicara en el controlador habría que
     * repetirlo tres veces, con el riesgo de olvidar una.
     *
     * @param  array<string, mixed>  $filtros
     * @return array<string, mixed>
     */
    public function filtrosConAlcance(array $filtros, ?User $usuario): array
    {
        if ($usuario?->hasRole('cliente')) {
            $filtros['cliente_id'] = $usuario->id;
        }

        return $filtros;
    }

    public function consultarInventario(array $filtros, ?User $usuario = null): LengthAwarePaginator
    {
        $filtros = $this->filtrosConAlcance($filtros, $usuario);

        $query = Referencia::query()
            ->with(['contenedor', 'cliente', 'ubicacionPatio']);

        if (!empty($filtros['cliente_id'])) {
            $query->where('cliente_id', $filtros['cliente_id']);
        }

        if (!empty($filtros['codigo'])) {
            $query->where('codigo', 'like', '%' . $filtros['codigo'] . '%');
        }

        if (!empty($filtros['modulo'])) {
            $query->whereHas('ubicacionPatio', function ($q) use ($filtros) {
                $q->where('modulo', $filtros['modulo']);
            });
        }

        if (!empty($filtros['fecha_desde'])) {
            $query->where('fecha_ingreso', '>=', $filtros['fecha_desde']);
        }

        if (!empty($filtros['fecha_hasta'])) {
            $query->where('fecha_ingreso', '<=', $filtros['fecha_hasta']);
        }

        $referencias = $query->orderBy('fecha_ingreso', 'desc')->paginate(20);

        $referencias->getCollection()->transform(function (Referencia $ref) {
            $fechaFin = $ref->fecha_salida ?? Carbon::now();
            $ref->dias_almacenamiento = $ref->fecha_ingreso
                ? (int) $ref->fecha_ingreso->diffInDays($fechaFin)
                : 0;

            return $ref;
        });

        return $referencias;
    }

    public function exportarInventario(array $filtros, ?User $usuario = null): InventarioExport
    {
        return new InventarioExport($this->filtrosConAlcance($filtros, $usuario));
    }

    public function exportarInventarioPdf(array $filtros, ?User $usuario = null)
    {
        $filtros = $this->filtrosConAlcance($filtros, $usuario);

        $query = Referencia::query()
            ->with(['contenedor', 'cliente', 'ubicacionPatio']);

        if (!empty($filtros['cliente_id'])) {
            $query->where('cliente_id', $filtros['cliente_id']);
        }

        if (!empty($filtros['codigo'])) {
            $query->where('codigo', 'like', '%' . $filtros['codigo'] . '%');
        }

        if (!empty($filtros['modulo'])) {
            $query->whereHas('ubicacionPatio', function ($q) use ($filtros) {
                $q->where('modulo', $filtros['modulo']);
            });
        }

        if (!empty($filtros['fecha_desde'])) {
            $query->where('fecha_ingreso', '>=', $filtros['fecha_desde']);
        }

        if (!empty($filtros['fecha_hasta'])) {
            $query->where('fecha_ingreso', '<=', $filtros['fecha_hasta']);
        }

        $referencias = $query->orderBy('fecha_ingreso', 'desc')->get();

        $referencias->transform(function (Referencia $ref) {
            $fechaFin = $ref->fecha_salida ?? Carbon::now();
            $ref->dias_almacenamiento = $ref->fecha_ingreso
                ? (int) $ref->fecha_ingreso->diffInDays($fechaFin)
                : 0;

            return $ref;
        });

        $pdf = Pdf::loadView('pdf.inventario', [
            'referencias' => $referencias,
            'filtros' => $filtros,
        ]);

        return $pdf->download('inventario_' . now()->format('Ymd_His') . '.pdf');
    }
}