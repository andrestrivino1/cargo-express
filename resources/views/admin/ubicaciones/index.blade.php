@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-geo-alt me-2"></i>Ubicaciones de Patio</h2>
    <a href="{{ route('admin.ubicaciones.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> Nueva Ubicación
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Módulo</th>
                        <th>Posición</th>
                        <th>Descripción</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ubicaciones as $ubicacion)
                        <tr>
                            <td>{{ $ubicacion->modulo }}</td>
                            <td>{{ $ubicacion->posicion }}</td>
                            <td>{{ $ubicacion->descripcion ?? '—' }}</td>
                            <td>
                                @if($ubicacion->activa)
                                    <span class="badge bg-success">Activa</span>
                                @else
                                    <span class="badge bg-secondary">Inactiva</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.ubicaciones.edit', $ubicacion) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-pencil"></i> Editar
                                </a>
                                <form action="{{ route('admin.ubicaciones.destroy', $ubicacion) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('¿Está seguro de eliminar esta ubicación?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-trash"></i> Eliminar
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">No hay ubicaciones registradas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-center mt-3">
            {{ $ubicaciones->links() }}
        </div>
    </div>
</div>
@endsection
