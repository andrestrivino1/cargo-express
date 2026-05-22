@extends('layouts.app')

@push('styles')
<style>
    .timeline {
        position: relative;
        padding-left: 40px;
    }
    .timeline::before {
        content: '';
        position: absolute;
        left: 18px;
        top: 0;
        bottom: 0;
        width: 3px;
        background-color: #dee2e6;
    }
    .timeline-item {
        position: relative;
        margin-bottom: 1.5rem;
    }
    .timeline-item::before {
        content: '';
        position: absolute;
        left: -28px;
        top: 12px;
        width: 14px;
        height: 14px;
        border-radius: 50%;
        background-color: #6c757d;
        border: 3px solid #fff;
        box-shadow: 0 0 0 2px #6c757d;
        z-index: 1;
    }
    .timeline-item.solicitud::before { background-color: #6f42c1; box-shadow: 0 0 0 2px #6f42c1; }
    .timeline-item.orden_servicio::before { background-color: #0d6efd; box-shadow: 0 0 0 2px #0d6efd; }
    .timeline-item.gate_event::before { background-color: #198754; box-shadow: 0 0 0 2px #198754; }
    .timeline-item.vaciado_programada::before { background-color: #0dcaf0; box-shadow: 0 0 0 2px #0dcaf0; }
    .timeline-item.vaciado_iniciada::before { background-color: #fd7e14; box-shadow: 0 0 0 2px #fd7e14; }
    .timeline-item.vaciado_finalizada::before { background-color: #20c997; box-shadow: 0 0 0 2px #20c997; }
    .timeline-item.novedad::before { background-color: #dc3545; box-shadow: 0 0 0 2px #dc3545; }
    .timeline-item.ubicacion::before { background-color: #ffc107; box-shadow: 0 0 0 2px #ffc107; }

    .timeline-card {
        border-left: 4px solid #6c757d;
    }
    .timeline-card.solicitud { border-left-color: #6f42c1; }
    .timeline-card.orden_servicio { border-left-color: #0d6efd; }
    .timeline-card.gate_event { border-left-color: #198754; }
    .timeline-card.vaciado_programada { border-left-color: #0dcaf0; }
    .timeline-card.vaciado_iniciada { border-left-color: #fd7e14; }
    .timeline-card.vaciado_finalizada { border-left-color: #20c997; }
    .timeline-card.novedad { border-left-color: #dc3545; }
    .timeline-card.ubicacion { border-left-color: #ffc107; }

    .photo-thumb {
        width: 80px;
        height: 80px;
        object-fit: cover;
        border-radius: 4px;
        border: 1px solid #dee2e6;
        cursor: pointer;
        transition: transform 0.2s;
    }
    .photo-thumb:hover {
        transform: scale(1.1);
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="d-flex align-items-center">
            <i class="bi bi-box-seam fs-4 me-2 text-primary"></i>
            <h2 class="mb-0">Trazabilidad: {{ $contenedor->numero }}</h2>
        </div>
        <div>
            <a href="{{ route('trazabilidad.index') }}" class="btn btn-outline-secondary me-2">
                <i class="bi bi-arrow-left me-1"></i> Volver
            </a>
            <a href="{{ route('trazabilidad.pdf', $contenedor) }}" class="btn btn-danger">
                <i class="bi bi-file-earmark-pdf me-1"></i> Descargar Historial PDF
            </a>
        </div>
    </div>

    <div class="row">
        {{-- Contenedor Info Card --}}
        <div class="col-lg-4 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>Información del Contenedor</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <td class="fw-semibold text-muted">Número:</td>
                            <td>{{ $contenedor->numero }}</td>
                        </tr>
                        <tr>
                            <td class="fw-semibold text-muted">Estado:</td>
                            <td>
                                <span class="badge bg-{{ $contenedor->estado?->color ?? 'secondary' }}">
                                    {{ $contenedor->estado?->label() ?? $contenedor->estado }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td class="fw-semibold text-muted">Tipo:</td>
                            <td>{{ $contenedor->tipo ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td class="fw-semibold text-muted">Placa:</td>
                            <td>{{ $contenedor->placa_vehiculo ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td class="fw-semibold text-muted">Ingreso:</td>
                            <td>{{ $contenedor->fecha_ingreso?->format('d/m/Y H:i') ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td class="fw-semibold text-muted">Salida:</td>
                            <td>{{ $contenedor->fecha_salida?->format('d/m/Y H:i') ?? 'En patio' }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            {{-- Cliente Info --}}
            @if($contenedor->ordenServicio?->solicitud?->cliente)
            <div class="card shadow-sm mt-3">
                <div class="card-header bg-secondary text-white">
                    <h6 class="mb-0"><i class="bi bi-person me-2"></i>Cliente</h6>
                </div>
                <div class="card-body">
                    <p class="mb-1"><strong>{{ $contenedor->ordenServicio->solicitud->cliente->name }}</strong></p>
                    <p class="mb-1 text-muted small">{{ $contenedor->ordenServicio->solicitud->cliente->email }}</p>
                    @if($contenedor->ordenServicio->solicitud->cliente->phone)
                    <p class="mb-0 text-muted small">{{ $contenedor->ordenServicio->solicitud->cliente->phone }}</p>
                    @endif
                </div>
            </div>
            @endif

            {{-- Documentos --}}
            @if($contenedor->ordenServicio?->solicitud?->documentos?->count() > 0)
            <div class="card shadow-sm mt-3">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0"><i class="bi bi-file-earmark me-2"></i>Documentos</h6>
                </div>
                <div class="list-group list-group-flush">
                    @foreach($contenedor->ordenServicio->solicitud->documentos as $doc)
                    <a href="{{ $doc->url }}" target="_blank" class="list-group-item list-group-item-action">
                        <i class="bi bi-file-earmark-text me-2"></i>{{ $doc->nombre }}
                    </a>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Resumen --}}
            <div class="card shadow-sm mt-3">
                <div class="card-header bg-warning text-dark">
                    <h6 class="mb-0"><i class="bi bi-bar-chart me-2"></i>Resumen</h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6 mb-2">
                            <div class="fs-4 fw-bold text-primary">{{ $contenedor->gateEvents->count() }}</div>
                            <small class="text-muted">Gate Events</small>
                        </div>
                        <div class="col-6 mb-2">
                            <div class="fs-4 fw-bold text-success">{{ $contenedor->ordenesVaciado->count() }}</div>
                            <small class="text-muted">Vaciados</small>
                        </div>
                        <div class="col-6">
                            <div class="fs-4 fw-bold text-info">{{ $contenedor->referencias->count() }}</div>
                            <small class="text-muted">Referencias</small>
                        </div>
                        <div class="col-6">
                            <div class="fs-4 fw-bold text-danger">
                                {{ $contenedor->ordenesVaciado->sum(fn($ov) => $ov->novedades->count()) }}
                            </div>
                            <small class="text-muted">Novedades</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Timeline --}}
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Línea de Tiempo</h5>
                </div>
                <div class="card-body">
                    @if($historial->count() > 0)
                    <div class="timeline">
                        @foreach($historial as $evento)
                        <div class="timeline-item {{ $evento['tipo'] }}">
                            <div class="card shadow-sm timeline-card {{ $evento['tipo'] }}">
                                <div class="card-body py-2 px-3">
                                    <div class="d-flex justify-content-between align-items-start mb-1">
                                        <div>
                                            @php
                                                $badgeColors = [
                                                    'solicitud' => 'bg-purple',
                                                    'orden_servicio' => 'bg-primary',
                                                    'gate_event' => 'bg-success',
                                                    'vaciado_programada' => 'bg-info',
                                                    'vaciado_iniciada' => 'bg-warning text-dark',
                                                    'vaciado_finalizada' => 'bg-teal',
                                                    'novedad' => 'bg-danger',
                                                    'ubicacion' => 'bg-warning text-dark',
                                                ];
                                                $badgeClass = $badgeColors[$evento['tipo']] ?? 'bg-secondary';
                                            @endphp
                                            <span class="badge {{ $badgeClass }} me-2">
                                                {{ strtoupper(str_replace('_', ' ', $evento['tipo'])) }}
                                            </span>
                                            <strong>{{ $evento['descripcion'] }}</strong>
                                        </div>
                                        <small class="text-muted text-nowrap ms-2">
                                            <i class="bi bi-clock me-1"></i>
                                            @if($evento['fecha'] instanceof \Carbon\Carbon)
                                                {{ $evento['fecha']->format('d/m/Y H:i') }}
                                            @else
                                                {{ $evento['fecha'] }}
                                            @endif
                                        </small>
                                    </div>

                                    <div class="small text-muted mb-1">
                                        <i class="bi bi-person me-1"></i>{{ $evento['usuario'] }}
                                    </div>

                                    @if(!empty($evento['detalles']))
                                    <div class="small">
                                        @foreach($evento['detalles'] as $clave => $valor)
                                            @if($valor)
                                            <span class="me-3">
                                                <strong>{{ ucfirst(str_replace('_', ' ', $clave)) }}:</strong> {{ $valor }}
                                            </span>
                                            @endif
                                        @endforeach
                                    </div>
                                    @endif

                                    @if($evento['fotos']->count() > 0)
                                    <div class="mt-2">
                                        <small class="text-muted d-block mb-1">
                                            <i class="bi bi-camera me-1"></i>Fotos ({{ $evento['fotos']->count() }}):
                                        </small>
                                        <div class="d-flex gap-2 flex-wrap">
                                            @foreach($evento['fotos'] as $foto)
                                            <a href="{{ $foto->url }}" target="_blank">
                                                <img src="{{ $foto->url }}"
                                                     alt="{{ $foto->nombre }}"
                                                     class="photo-thumb"
                                                     title="{{ $foto->nombre }}">
                                            </a>
                                            @endforeach
                                        </div>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-clock-history display-4 d-block mb-3"></i>
                        <h5>No se encontraron eventos</h5>
                        <p>Este contenedor no tiene eventos registrados en su historial.</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
