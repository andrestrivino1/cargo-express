@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2><i class="bi bi-list-ul me-2"></i>Referencias del Contenedor</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('gate-in.index') }}">Ingreso</a></li>
                <li class="breadcrumb-item active">Referencias - {{ $contenedor->numero }}</li>
            </ol>
        </nav>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('referencias.create', $contenedor) }}" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i> Agregar Referencias
        </a>
        @if($contenedor->gateEvents->where('tipo.value', 'gate_in')->first())
        <a href="{{ route('gate-in.pdf', $contenedor->gateEvents->where('tipo.value', 'gate_in')->first()) }}"
           class="btn btn-outline-danger">
            <i class="bi bi-file-earmark-pdf me-1"></i> Descargar Resumen PDF
        </a>
        @endif
    </div>
</div>

{{-- Info del Contenedor --}}
<div class="card mb-4">
    <div class="card-body">
        <div class="row">
            <div class="col-md-3">
                <small class="text-muted">Contenedor</small>
                <p class="fw-bold mb-0">{{ $contenedor->numero }}</p>
            </div>
            <div class="col-md-3">
                <small class="text-muted">Cliente</small>
                <p class="fw-bold mb-0">{{ $contenedor->ordenServicio->solicitud->cliente->name ?? 'N/A' }}</p>
            </div>
            <div class="col-md-3">
                <small class="text-muted">Estado</small>
                <p class="mb-0">
                    <span class="badge bg-{{ $contenedor->estado->color() }}">
                        {{ $contenedor->estado->label() }}
                    </span>
                </p>
            </div>
            <div class="col-md-3">
                <small class="text-muted">Total Referencias</small>
                <p class="fw-bold mb-0">{{ $contenedor->referencias->count() }}</p>
            </div>
        </div>
    </div>
</div>

{{-- Tabla de Referencias --}}
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Referencias Registradas</h5>
    </div>
    <div class="card-body">
        @if($contenedor->referencias->count() > 0)
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Producto</th>
                        <th>Codigo</th>
                        <th>Descripcion</th>
                        <th>Cantidad</th>
                        <th>Unidad</th>
                        <th>Ubicacion</th>
                        <th>Fecha Ingreso</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($contenedor->referencias as $index => $referencia)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $referencia->producto->nombre ?? '-' }}</td>
                        <td><strong>{{ $referencia->codigo }}</strong></td>
                        <td>{{ $referencia->descripcion ?? '-' }}</td>
                        <td>
                            <span class="badge bg-info">{{ $referencia->cantidad_actual }}</span>
                            @if($referencia->cantidad_actual !== $referencia->cantidad_inicial)
                            <small class="text-muted">/ {{ $referencia->cantidad_inicial }}</small>
                            @endif
                        </td>
                        <td>{{ $referencia->unidad_medida ?? 'unidades' }}</td>
                        <td>
                            @if($referencia->ubicacionPatio)
                            {{ $referencia->ubicacionPatio->modulo }} - {{ $referencia->ubicacionPatio->posicion }}
                            @else
                            <span class="text-muted">Sin asignar</span>
                            @endif
                        </td>
                        <td>{{ $referencia->fecha_ingreso->format('d/m/Y H:i') }}</td>
                        <td class="text-end">
                            <a href="{{ route('sticker.show', $referencia) }}"
                               class="btn btn-sm btn-outline-secondary" title="Ver Sticker">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="{{ route('sticker.print', $referencia) }}"
                               class="btn btn-sm btn-outline-primary" title="Imprimir Sticker">
                                <i class="bi bi-printer"></i>
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="text-center py-4">
            <i class="bi bi-inbox fs-1 text-muted"></i>
            <p class="text-muted mt-2">No se han registrado referencias para este contenedor.</p>
            <a href="{{ route('referencias.create', $contenedor) }}" class="btn btn-primary">
                <i class="bi bi-plus-circle me-1"></i> Agregar Referencias
            </a>
        </div>
        @endif
    </div>
</div>
@endsection