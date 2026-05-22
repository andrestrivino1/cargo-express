@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Solicitud #{{ $solicitud->id }}</h1>
    <a href="{{ route('solicitudes.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Volver
    </a>
</div>

{{-- Detalle de la solicitud --}}
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white">
        <h5 class="card-title mb-0">Detalle de la Solicitud</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <dl class="row mb-0">
                    <dt class="col-sm-5">Contenedor:</dt>
                    <dd class="col-sm-7">{{ $solicitud->numero_contenedor }}</dd>

                    <dt class="col-sm-5">Cliente:</dt>
                    <dd class="col-sm-7">{{ $solicitud->cliente->name }}</dd>

                    <dt class="col-sm-5">Naviera:</dt>
                    <dd class="col-sm-7">{{ $solicitud->naviera ?? 'N/A' }}</dd>

                    <dt class="col-sm-5">Puerto de Origen:</dt>
                    <dd class="col-sm-7">{{ $solicitud->puerto_origen ?? 'N/A' }}</dd>
                </dl>
            </div>
            <div class="col-md-6">
                <dl class="row mb-0">
                    <dt class="col-sm-5">Estado:</dt>
                    <dd class="col-sm-7">
                        <span class="badge bg-{{ $solicitud->estado->color() }}">
                            {{ $solicitud->estado->label() }}
                        </span>
                    </dd>

                    <dt class="col-sm-5">Fecha:</dt>
                    <dd class="col-sm-7">{{ $solicitud->fecha_solicitud->format('d/m/Y H:i') }}</dd>
                </dl>
            </div>
        </div>

        @if($solicitud->descripcion)
            <hr>
            <p class="mb-0"><strong>Descripcion:</strong></p>
            <p class="mb-0">{{ $solicitud->descripcion }}</p>
        @endif
    </div>
</div>

{{-- Documentos --}}
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white">
        <h5 class="card-title mb-0">Documentos</h5>
    </div>
    <div class="card-body">
        @if($solicitud->documentos->isNotEmpty())
            @php
                $imagenes = $solicitud->documentos->filter(fn($d) => str_starts_with($d->tipo_mime, 'image/'));
                $archivos = $solicitud->documentos->reject(fn($d) => str_starts_with($d->tipo_mime, 'image/'));
            @endphp

            {{-- Galería de imágenes --}}
            @if($imagenes->isNotEmpty())
                <p class="text-muted small mb-2"><i class="bi bi-images me-1"></i>Imágenes adjuntas</p>
                <div class="row g-2 mb-3">
                    @foreach($imagenes as $imagen)
                        <div class="col-6 col-sm-4 col-md-3">
                            <a href="{{ $imagen->url }}" target="_blank" title="{{ $imagen->nombre }}">
                                <img src="{{ $imagen->url }}"
                                     alt="{{ $imagen->nombre }}"
                                     class="img-fluid rounded border"
                                     style="width:100%; height:110px; object-fit:cover; transition: transform .2s;"
                                     onmouseover="this.style.transform='scale(1.04)'"
                                     onmouseout="this.style.transform='scale(1)'">
                            </a>
                            <div class="text-muted mt-1" style="font-size:0.7rem; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="{{ $imagen->nombre }}">
                                {{ $imagen->nombre }}
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- PDFs y otros archivos --}}
            @if($archivos->isNotEmpty())
                <p class="text-muted small mb-2"><i class="bi bi-paperclip me-1"></i>Archivos adjuntos</p>
                <ul class="list-group list-group-flush">
                    @foreach($archivos as $documento)
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span>
                                <i class="bi bi-file-earmark-pdf text-danger me-2"></i>{{ $documento->nombre }}
                            </span>
                            <a href="{{ $documento->url }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-download me-1"></i> Descargar
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        @else
            <p class="text-muted mb-0">No hay documentos adjuntos.</p>
        @endif
    </div>
</div>


{{-- Orden de Servicio --}}
@if($solicitud->ordenServicio)
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Orden de Servicio</h5>
            <a href="{{ route('solicitudes.orden-servicio.pdf', $solicitud) }}" class="btn btn-sm btn-outline-danger">
                <i class="bi bi-file-pdf me-1"></i> Descargar PDF
            </a>
        </div>
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-3">Coordinador:</dt>
                <dd class="col-sm-9">{{ $solicitud->ordenServicio->coordinador->name }}</dd>

                <dt class="col-sm-3">Vehiculo:</dt>
                <dd class="col-sm-9">{{ $solicitud->ordenServicio->vehiculo }}</dd>

                <dt class="col-sm-3">Conductor:</dt>
                <dd class="col-sm-9">{{ $solicitud->ordenServicio->conductor }}</dd>

                <dt class="col-sm-3">Cedula Conductor:</dt>
                <dd class="col-sm-9">{{ $solicitud->ordenServicio->conductor_documento ?? 'N/A' }}</dd>

                <dt class="col-sm-3">Cita en Puerto:</dt>
                <dd class="col-sm-9">{{ $solicitud->ordenServicio->cita_puerto->format('d/m/Y H:i') }}</dd>

                <dt class="col-sm-3">Estado:</dt>
                <dd class="col-sm-9">
                    <span class="badge bg-{{ $solicitud->ordenServicio->estado->color() }}">
                        {{ $solicitud->ordenServicio->estado->label() }}
                    </span>
                </dd>
            </dl>
        </div>
    </div>
@else
    @can('asignar', $solicitud)
        @if(auth()->user()->can('solicitudes.asignar'))
            <div class="text-center py-3">
                <a href="{{ route('solicitudes.asignar', $solicitud) }}" class="btn btn-success">
                    <i class="bi bi-check2-square me-1"></i> Asignar Orden de Servicio
                </a>
            </div>
        @endif
    @endcan
@endif
@endsection