@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-box-arrow-in-right me-2"></i>Ingreso de Mercancía</h2>
    @can('ingreso.crear')
    <a href="{{ route('ingreso.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i> Nuevo Ingreso
    </a>
    @endcan
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2">
            <div class="col-md-5">
                <input type="text" name="bl" value="{{ request('bl') }}" class="form-control" placeholder="Buscar por BL">
            </div>
            <div class="col-md-2">
                <button class="btn btn-outline-secondary w-100"><i class="bi bi-search"></i> Filtrar</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>BL</th>
                    <th>Cliente</th>
                    <th>Contenedores</th>
                    <th>Fecha ingreso</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($ingresos as $ingreso)
                <tr>
                    <td>
                        {{ $ingreso->bl }}
                        @if ($ingreso->bl_por_confirmar)
                        <span class="badge bg-warning text-dark ms-1" title="BL provisional (número de contenedor). Editar para confirmar.">
                            <i class="bi bi-exclamation-triangle"></i> BL por confirmar
                        </span>
                        @endif
                    </td>
                    <td>{{ $ingreso->cliente?->name ?? '—' }}</td>
                    <td>{{ $ingreso->contenedores_count }}</td>
                    <td>{{ $ingreso->fecha_ingreso?->format('d/m/Y') }}</td>
                    <td class="text-end">
                        <a href="{{ route('ingreso.show', $ingreso) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>
                        @role('administrador|coordinador')
                        <a href="{{ route('ingreso.editar', $ingreso) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                        @endrole
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center text-muted py-4">No hay ingresos registrados.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $ingresos->withQueryString()->links() }}</div>
@endsection
