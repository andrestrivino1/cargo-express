@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-start mb-4">
    <div>
        <h2><i class="bi bi-box-arrow-in-right me-2"></i>Ingreso — BL {{ $ingreso->bl }}</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('ingreso.index') }}">Ingreso</a></li>
                <li class="breadcrumb-item active">BL {{ $ingreso->bl }}</li>
            </ol>
        </nav>
    </div>
    @role('administrador|coordinador')
    <a href="{{ route('ingreso.editar', $ingreso) }}" class="btn btn-outline-secondary"><i class="bi bi-pencil me-1"></i> Editar</a>
    @endrole
</div>

@if ($ingreso->bl_por_confirmar)
<div class="alert alert-warning">
    <i class="bi bi-exclamation-triangle me-1"></i>
    El <strong>BL es provisional</strong> (se usó el número de contenedor al importar).
    @role('administrador|coordinador')<a href="{{ route('ingreso.editar', $ingreso) }}" class="alert-link">Edita el ingreso</a> para poner el BL real.@else Pide a un administrador/coordinador que lo confirme.@endrole
</div>
@endif

@php
    // Compatibilidad: documentos del ingreso (nuevo) + de sus contenedores (legados feature 005)
    $documentos = $ingreso->documentos->concat($ingreso->contenedores->flatMap->documentos);
@endphp

<div class="row">
    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-header">Datos del ingreso</div>
            <ul class="list-group list-group-flush">
                <li class="list-group-item"><strong>BL:</strong> {{ $ingreso->bl }}</li>
                <li class="list-group-item"><strong>Cliente:</strong> {{ $ingreso->cliente?->name ?? '—' }}</li>
                <li class="list-group-item"><strong>Fecha de ingreso:</strong> {{ $ingreso->fecha_ingreso?->format('d/m/Y') }}</li>
                <li class="list-group-item"><strong>Contenedores:</strong> {{ $ingreso->contenedores->count() }}</li>
            </ul>
        </div>

        <div class="card mb-3">
            <div class="card-header">Documentos soporte</div>
            <ul class="list-group list-group-flush">
                @forelse ($documentos as $doc)
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span><i class="bi {{ $doc->icono }} me-1"></i> {{ \App\Enums\DocumentoCategoria::tryFrom($doc->categoria ?? '')?->label() ?? $doc->nombre }}</span>
                    <a href="{{ $doc->url }}" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="bi bi-download"></i></a>
                </li>
                @empty
                <li class="list-group-item text-muted">Sin documentos.</li>
                @endforelse
            </ul>
        </div>
    </div>

    <div class="col-lg-8">
        @foreach ($ingreso->contenedores as $contenedor)
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between">
                <span><i class="bi bi-box-seam me-1"></i> Contenedor {{ $contenedor->numero }}</span>
                <span class="text-muted small">{{ $contenedor->tipo_mercancia }}</span>
            </div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr><th>Referencia</th><th>Descripción</th><th>Unidad</th><th>Peso</th><th>Cantidad</th><th>Ubicación</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($contenedor->referencias as $ref)
                        <tr>
                            <td>{{ $ref->codigo }}</td>
                            <td>{{ $ref->descripcion }}</td>
                            <td>{{ $ref->unidad_medida }}</td>
                            <td>{{ $ref->peso }}</td>
                            <td>{{ $ref->cantidad_actual }}</td>
                            <td>
                                @if ($ref->ubicacionPatio)
                                    {{ $ref->ubicacionPatio->modulo }} - {{ $ref->ubicacionPatio->posicion }}
                                @else
                                    <span class="badge bg-warning text-dark">Sin ubicar</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endsection
