@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Solicitudes</h1>
    @can('solicitudes.crear')
        <a href="{{ route('solicitudes.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Nueva Solicitud
        </a>
    @endcan
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Contenedor</th>
                        @unless(auth()->user()->hasRole('cliente'))
                            <th>Cliente</th>
                        @endunless
                        <th>Estado</th>
                        <th>Fecha</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($solicitudes as $solicitud)
                        <tr>
                            <td>{{ $solicitud->id }}</td>
                            <td>{{ $solicitud->numero_contenedor }}</td>
                            @unless(auth()->user()->hasRole('cliente'))
                                <td>{{ $solicitud->cliente->name ?? 'N/A' }}</td>
                            @endunless
                            <td>
                                <span class="badge bg-{{ $solicitud->estado->color() }}">
                                    {{ $solicitud->estado->label() }}
                                </span>
                            </td>
                            <td>{{ $solicitud->fecha_solicitud->format('d/m/Y H:i') }}</td>
                            <td>
                                <a href="{{ route('solicitudes.show', $solicitud) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i> Ver
                                </a>
                                @can('solicitudes.asignar')
                                    @if($solicitud->estado->value === 'pendiente')
                                        <a href="{{ route('solicitudes.asignar', $solicitud) }}" class="btn btn-sm btn-success ms-1">
                                            <i class="bi bi-check2-square"></i> Asignar
                                        </a>
                                    @endif
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">
                                No hay solicitudes registradas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-3">
    {{ $solicitudes->links() }}
</div>
@endsection