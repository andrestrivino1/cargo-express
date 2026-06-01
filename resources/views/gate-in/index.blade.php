@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-box-arrow-in-right me-2"></i>Ingreso</h2>
    <a href="{{ route('gate-in.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i> Registrar Ingreso
    </a>
</div>

{{-- Contenedores Pendientes de Ingreso --}}
<div class="card mb-4">
    <div class="card-header bg-warning bg-opacity-10">
        <h5 class="mb-0"><i class="bi bi-clock-history me-1"></i> Contenedores Pendientes de Ingreso</h5>
    </div>
    <div class="card-body">
        @if($pendientes->count() > 0)
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>No. Contenedor</th>
                        <th>Cliente</th>
                        <th>Orden de Servicio</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pendientes as $contenedor)
                    <tr>
                        <td><strong>{{ $contenedor->numero }}</strong></td>
                        <td>{{ $contenedor->ordenServicio->solicitud->cliente->name ?? 'N/A' }}</td>
                        <td>#{{ $contenedor->ordenServicio->id }}</td>
                        <td>
                            <span class="badge bg-{{ $contenedor->estado->color() }}">
                                {{ $contenedor->estado->label() }}
                            </span>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('gate-in.create', ['orden_servicio_id' => $contenedor->ordenServicio->id]) }}"
                               class="btn btn-sm btn-success">
                                <i class="bi bi-box-arrow-in-right me-1"></i> Registrar Ingreso
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <p class="text-muted mb-0">No hay contenedores pendientes de ingreso.</p>
        @endif
    </div>
</div>

{{-- Ultimos Ingresos --}}
<div class="card">
    <div class="card-header bg-success bg-opacity-10">
        <h5 class="mb-0"><i class="bi bi-check-circle me-1"></i> Ultimos Ingresos</h5>
    </div>
    <div class="card-body">
        @if($ultimosIngresos->count() > 0)
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>No. Contenedor</th>
                        <th>Cliente</th>
                        <th>Placa</th>
                        <th>Portero</th>
                        <th>Fecha/Hora</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($ultimosIngresos as $evento)
                    <tr>
                        <td><strong>{{ $evento->contenedor->numero }}</strong></td>
                        <td>{{ $evento->contenedor->ordenServicio->solicitud->cliente->name ?? 'N/A' }}</td>
                        <td>{{ $evento->contenedor->placa_vehiculo }}</td>
                        <td>{{ $evento->usuario->name }}</td>
                        <td>{{ $evento->hora->format('d/m/Y H:i') }}</td>
                        <td>
                            <span class="badge bg-{{ $evento->tipo->color() }}">
                                {{ $evento->tipo->label() }}
                            </span>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('referencias.index', $evento->contenedor) }}"
                               class="btn btn-sm btn-outline-primary" title="Referencias">
                                <i class="bi bi-list-ul"></i>
                            </a>
                            <a href="{{ route('gate-in.pdf', $evento) }}"
                               class="btn btn-sm btn-outline-danger" title="Descargar PDF">
                                <i class="bi bi-file-earmark-pdf"></i>
                            </a>
                            @role('administrador|coordinador')
                                <a href="{{ route('gate-in.editar', $evento) }}"
                                   class="btn btn-sm btn-outline-primary" title="Editar ingreso">
                                    <i class="bi bi-pencil"></i>
                                </a>
                            @endrole
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <p class="text-muted mb-0">No se han registrado ingresos aun.</p>
        @endif
    </div>
</div>
@endsection