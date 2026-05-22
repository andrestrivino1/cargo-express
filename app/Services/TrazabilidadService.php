<?php

namespace App\Services;

use App\Models\Contenedor;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class TrazabilidadService
{
    /**
     * Buscar contenedor por número con todas las relaciones cargadas.
     */
    public function buscarContenedor(string $numero): ?Contenedor
    {
        return Contenedor::where('numero', $numero)
            ->with([
                'ordenServicio.solicitud.documentos',
                'ordenServicio.solicitud.cliente',
                'gateEvents.photos',
                'gateEvents.usuario',
                'referencias.ubicacionPatio',
                'referencias.novedades.photos',
                'ordenesVaciado.novedades.photos',
                'ordenesVaciado.supervisor',
            ])
            ->first();
    }

    /**
     * Obtener historial cronológico completo de un contenedor.
     */
    public function obtenerHistorial(Contenedor $contenedor): Collection
    {
        $historial = collect();

        // Solicitud creada
        $solicitud = $contenedor->ordenServicio?->solicitud;
        if ($solicitud) {
            $historial->push([
                'fecha' => $solicitud->fecha_solicitud ?? $solicitud->created_at,
                'tipo' => 'solicitud',
                'descripcion' => 'Solicitud de servicio creada',
                'usuario' => $solicitud->cliente?->name ?? 'N/A',
                'detalles' => [
                    'naviera' => $solicitud->naviera,
                    'puerto_origen' => $solicitud->puerto_origen,
                    'descripcion' => $solicitud->descripcion,
                    'estado' => $solicitud->estado?->value ?? $solicitud->estado,
                ],
                'fotos' => collect(),
            ]);
        }

        // Orden de servicio creada
        $ordenServicio = $contenedor->ordenServicio;
        if ($ordenServicio) {
            $historial->push([
                'fecha' => $ordenServicio->created_at,
                'tipo' => 'orden_servicio',
                'descripcion' => 'Orden de servicio creada',
                'usuario' => $ordenServicio->coordinador?->name ?? 'N/A',
                'detalles' => [
                    'vehiculo' => $ordenServicio->vehiculo,
                    'conductor' => $ordenServicio->conductor,
                    'conductor_documento' => $ordenServicio->conductor_documento,
                    'cita_puerto' => $ordenServicio->cita_puerto?->format('d/m/Y H:i'),
                    'estado' => $ordenServicio->estado?->value ?? $ordenServicio->estado,
                ],
                'fotos' => collect(),
            ]);
        }

        // Gate events (gate_in, gate_out)
        foreach ($contenedor->gateEvents as $gateEvent) {
            $historial->push([
                'fecha' => $gateEvent->hora ?? $gateEvent->created_at,
                'tipo' => 'gate_event',
                'descripcion' => $gateEvent->tipo?->label() ?? $gateEvent->tipo,
                'usuario' => $gateEvent->usuario?->name ?? 'N/A',
                'detalles' => [
                    'tipo_gate' => $gateEvent->tipo?->value ?? $gateEvent->tipo,
                    'estado_fisico' => $gateEvent->estado_fisico,
                    'notas' => $gateEvent->notas,
                ],
                'fotos' => $gateEvent->photos,
            ]);
        }

        // Ordenes de vaciado (programada, iniciada, finalizada)
        foreach ($contenedor->ordenesVaciado as $ordenVaciado) {
            // Programada
            if ($ordenVaciado->fecha_programada) {
                $historial->push([
                    'fecha' => $ordenVaciado->fecha_programada,
                    'tipo' => 'vaciado_programada',
                    'descripcion' => 'Vaciado programado',
                    'usuario' => $ordenVaciado->supervisor?->name ?? 'N/A',
                    'detalles' => [
                        'estado' => $ordenVaciado->estado?->value ?? $ordenVaciado->estado,
                        'notas' => $ordenVaciado->notas,
                    ],
                    'fotos' => collect(),
                ]);
            }

            // Iniciada
            if ($ordenVaciado->fecha_inicio) {
                $novedadesInicio = $ordenVaciado->novedades->filter(
                    fn ($n) => $n->created_at <= $ordenVaciado->fecha_inicio->copy()->addMinutes(5)
                );

                $historial->push([
                    'fecha' => $ordenVaciado->fecha_inicio,
                    'tipo' => 'vaciado_iniciada',
                    'descripcion' => 'Vaciado iniciado',
                    'usuario' => $ordenVaciado->supervisor?->name ?? 'N/A',
                    'detalles' => [
                        'estado' => $ordenVaciado->estado?->value ?? $ordenVaciado->estado,
                        'notas' => $ordenVaciado->notas,
                    ],
                    'fotos' => collect(),
                ]);
            }

            // Finalizada
            if ($ordenVaciado->fecha_fin) {
                $historial->push([
                    'fecha' => $ordenVaciado->fecha_fin,
                    'tipo' => 'vaciado_finalizada',
                    'descripcion' => 'Vaciado finalizado',
                    'usuario' => $ordenVaciado->supervisor?->name ?? 'N/A',
                    'detalles' => [
                        'estado' => $ordenVaciado->estado?->value ?? $ordenVaciado->estado,
                        'notas' => $ordenVaciado->notas,
                    ],
                    'fotos' => collect(),
                ]);
            }

            // Novedades del vaciado
            foreach ($ordenVaciado->novedades as $novedad) {
                $historial->push([
                    'fecha' => $novedad->created_at,
                    'tipo' => 'novedad',
                    'descripcion' => 'Novedad: ' . ($novedad->tipo?->label() ?? $novedad->tipo),
                    'usuario' => $novedad->operador?->name ?? 'N/A',
                    'detalles' => [
                        'tipo_novedad' => $novedad->tipo?->value ?? $novedad->tipo,
                        'descripcion' => $novedad->descripcion,
                        'referencia' => $novedad->referencia?->codigo,
                    ],
                    'fotos' => $novedad->photos,
                ]);
            }
        }

        // Ubicación assignments (referencias)
        foreach ($contenedor->referencias as $referencia) {
            if ($referencia->ubicacion_patio_id) {
                $historial->push([
                    'fecha' => $referencia->fecha_ingreso ?? $referencia->created_at,
                    'tipo' => 'ubicacion',
                    'descripcion' => 'Referencia ' . $referencia->codigo . ' ubicada en ' .
                        ($referencia->ubicacionPatio?->modulo ?? '') . '-' .
                        ($referencia->ubicacionPatio?->posicion ?? ''),
                    'usuario' => $referencia->cliente?->name ?? 'N/A',
                    'detalles' => [
                        'codigo' => $referencia->codigo,
                        'descripcion' => $referencia->descripcion,
                        'cantidad' => $referencia->cantidad_inicial,
                        'modulo' => $referencia->ubicacionPatio?->modulo,
                        'posicion' => $referencia->ubicacionPatio?->posicion,
                    ],
                    'fotos' => collect(),
                ]);
            }
        }

        // Ordenar cronológicamente
        return $historial->sortBy('fecha')->values();
    }

    /**
     * Exportar historial completo del contenedor como PDF.
     */
    public function exportarHistorialPdf(Contenedor $contenedor): \Illuminate\Http\Response
    {
        $historial = $this->obtenerHistorial($contenedor);

        $diasAlmacenamiento = 0;
        if ($contenedor->fecha_ingreso) {
            $fechaFin = $contenedor->fecha_salida ?? Carbon::now();
            $diasAlmacenamiento = (int) $contenedor->fecha_ingreso->diffInDays($fechaFin);
        }

        $pdf = Pdf::loadView('pdf.historial-contenedor', [
            'contenedor' => $contenedor,
            'historial' => $historial,
            'diasAlmacenamiento' => $diasAlmacenamiento,
        ]);

        $pdf->setPaper('A4', 'portrait');

        return $pdf->download('historial-contenedor-' . $contenedor->numero . '.pdf');
    }
}
